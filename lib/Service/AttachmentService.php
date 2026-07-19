<?php
declare(strict_types=1);

namespace OCA\Charity\Service;

use OCA\Charity\Db\Acl;
use OCA\Charity\Db\cc_attachment;
use OCA\Charity\Db\cc_attachmentMapper;
use OCA\Charity\Db\cc_Case;
use OCA\Charity\Db\cc_CaseMapper;
use OCA\Charity\Db\cc_PaymentMapper;
use OCA\Charity\Db\cc_UpdateMapper;
use OCP\DB\Exception as DBException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class AttachmentService {
	private cc_attachmentMapper $mapper;
	private cc_CaseMapper $caseMapper;
	private cc_PaymentMapper $paymentMapper;
	private cc_UpdateMapper $updateMapper;
	private PermissionService $permissionService;
	private IRootFolder $root;
	private IConfig $config;
	private IDBConnection $db;
	private LoggerInterface $logger;
	private ?string $userId;

	public function __construct(
		cc_attachmentMapper $mapper,
		cc_CaseMapper $caseMapper,
		cc_PaymentMapper $paymentMapper,
		cc_UpdateMapper $updateMapper,
		PermissionService $permissionService,
		IRootFolder $root,
		IConfig $config,
		IDBConnection $db,
		LoggerInterface $logger,
		$userId
	) {
		$this->mapper = $mapper;
		$this->caseMapper = $caseMapper;
		$this->paymentMapper = $paymentMapper;
		$this->updateMapper = $updateMapper;
		$this->permissionService = $permissionService;
		$this->root = $root;
		$this->config = $config;
		$this->db = $db;
		$this->logger = $logger;
		$this->userId = $userId;
	}

	/**
	 * Get the configured Group Folder ID.
	 */
	private function getGroupFolderId(): string {
		return $this->config->getAppValue('charity', 'groupFolderId', '1');
	}

	/**
	 * Get the Group Folder mount point name from the database.
	 */
	private function getGroupFolderName(string $groupId): ?string {
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('mount_point')
				->from('group_folders')
				->where($qb->expr()->eq('folder_id', $qb->createNamedParameter($groupId)));
			$result = $qb->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();
			return $row ? $row['mount_point'] : null;
		} catch (DBException $e) {
			return null;
		}
	}

	/**
	 * Get the Charity folder (Group Folder) via the current user's folder view.
	 */
	public function getCasesFolder(): Folder {
		$groupId = $this->getGroupFolderId();
		$groupName = $this->getGroupFolderName($groupId);
		if (!$groupName) {
			throw new \RuntimeException('Group folder not found: ' . $groupId);
		}
		if (!$this->userId) {
			throw new \RuntimeException('No user ID available for group folder access');
		}
		$userFolder = $this->root->getUserFolder($this->userId);
		return $userFolder->get($groupName);
	}

	/**
	 * Create an attachment record and store the file under Charity/.
	 */
	public function create(string $objectType, int $objectId, array $file, string $userId) {
		$mapper = $this->getMapperForObject($objectType);
		$object = $mapper->find($objectId);

		$permObjectType = $objectType;
		$permObjectId = $objectId;
		if (($objectType === 'cc_Payment' || $objectType === 'cc_Update') && $object->getCaseId()) {
			$permObjectType = 'cc_Case';
			$permObjectId = $object->getCaseId();
		}
		$this->permissionService->checkPermission($this->caseMapper, $permObjectType, $permObjectId, Acl::PERMISSION_READ);

		$attachment = new cc_attachment();
		$attachment->setObjectId($objectId);
		$attachment->setObjectType($objectType);
		$attachment->setCreated(date('Y-m-d H:i:s'));
		$attachment->setUpdated(date('Y-m-d H:i:s'));
		$attachment->setIsactive(true);
		$attachment->setTag($file['tag'] ?? '');
		$attachment->setDescription($file['description'] ?? '');
		$attachment->setData($file['name']);
		$attachment->setSize((int)($file['size'] ?? 0));

		$base = $file['data'];
		if (strpos($base, ',') !== false) {
			$base = explode(',', $base, 2)[1];
		}
		$base = base64_decode($base);

		$filename = str_replace([':', '-', ' '], '_', date('Y-m-d H:i:s')) . '_' . $file['name'];
		$attachment->setName($filename);

		$folder = $this->getObjectFolder($objectType, $objectId);
		$fileNode = $folder->newFile($filename);
		$fileNode->putContent($base);

		$attachment->setUrl($this->getFileUrl($objectType, $objectId, $fileNode->getId()));

		return $this->mapper->insert($attachment);
	}

	/**
	 * Update attachment metadata.
	 */
	public function update(int $id, array $data): cc_attachment {
		$attachment = $this->mapper->find($id);

		$permObjectType = $attachment->getObjectType();
		$permObjectId = (int)$attachment->getObjectId();
		if (($permObjectType === 'cc_Payment' || $permObjectType === 'cc_Update')) {
			$parent = $permObjectType === 'cc_Payment'
				? $this->paymentMapper->find($permObjectId)
				: $this->updateMapper->find($permObjectId);
			if ($parent->getCaseId()) {
				$permObjectType = 'cc_Case';
				$permObjectId = $parent->getCaseId();
			}
		}
		$this->permissionService->checkPermission(
			$this->caseMapper,
			$permObjectType,
			$permObjectId,
			Acl::PERMISSION_MANAGE
		);

		if (isset($data['isactive'])) {
			$attachment->setIsactive((bool)$data['isactive']);
		}
		if (isset($data['tag'])) {
			$attachment->setTag($data['tag']);
		}
		if (isset($data['size'])) {
			$attachment->setSize((int)$data['size']);
		}
		if (isset($data['data'])) {
			$attachment->setData($data['data']);
		}
		if (isset($data['url'])) {
			$attachment->setUrl($data['url']);
		}
		if (isset($data['name'])) {
			$attachment->setName($data['name']);
		}
		if (isset($data['description'])) {
			$attachment->setDescription($data['description']);
		}

		$attachment->setUpdated(date('Y-m-d H:i:s'));

		return $this->mapper->update($attachment);
	}

	/**
	 * Delete an attachment record and its file.
	 */
	public function delete(int $id, ?string $userId = null): void {
		$attachment = $this->mapper->find($id);
		$objectType = $attachment->getObjectType();
		$objectId = (int)$attachment->getObjectId();

		$permObjectType = $objectType;
		$permObjectId = $objectId;
		if (($permObjectType === 'cc_Payment' || $permObjectType === 'cc_Update')) {
			$parent = $permObjectType === 'cc_Payment'
				? $this->paymentMapper->find($permObjectId)
				: $this->updateMapper->find($permObjectId);
			if ($parent->getCaseId()) {
				$permObjectType = 'cc_Case';
				$permObjectId = $parent->getCaseId();
			}
		}
		$this->permissionService->checkPermission($this->caseMapper, $permObjectType, $permObjectId, Acl::PERMISSION_MANAGE);

		$folder = $this->getObjectFolder($objectType, $objectId);
		try {
			$file = $this->getFileByName($folder, $attachment->getName());
			if ($file->isDeletable()) {
				$file->delete();
			}
		} catch (NotFoundException $e) {
			$this->logger->warning('Charity: Attachment file not found for deletion: ' . $attachment->getName(), ['app' => 'charity']);
		}

		$this->mapper->delete($attachment);
	}

	/**
	 * Delete all attachments for an object.
	 */
	public function deleteAllInCase(int $objectId, string $objectType, ?string $userId = null): void {
		$this->deleteAllForObject($objectId, $objectType, $userId);
	}

	/**
	 * Delete all attachments for an object.
	 */
	public function deleteAllForObject(int $objectId, string $objectType, ?string $userId = null): void {
		$attachments = $this->mapper->findAll($objectId, $objectType);
		foreach ($attachments as $attachment) {
			$this->delete($attachment->getId(), $userId ?? $this->userId);
		}
	}

	/**
	 * Delete the case attachment folder.
	 */
	public function deleteCaseFolder(int $caseId): void {
		$case = $this->caseMapper->find($caseId);
		if ($case === null) {
			return;
		}
		$folder = $this->getCasesFolder();
		$this->deleteSubFolder($folder, $this->getCaseFolderName($case));
	}

	/**
	 * Delete an object's attachment folder (payments, updates, etc.).
	 */
	public function deleteObjectFolder(int $objectId, string $objectType): void {
		$folder = $this->getCasesFolder();
		$this->deleteSubFolder($folder, $this->getSubFolderName($objectType, $objectId));
	}

	private function deleteSubFolder(Folder $parent, string $relativePath): void {
		try {
			if (!$parent->nodeExists($relativePath)) {
				return;
			}
			$node = $parent->get($relativePath);
			if ($node instanceof Folder && $node->isDeletable()) {
				$node->delete();
			}
		} catch (\OCP\Files\NotFoundException $e) {
			$this->logger->warning('Charity: Folder not found for deletion: ' . $relativePath, ['app' => 'charity']);
		}
	}

	/**
	 * Find all active attachments for an object type.
	 *
	 * If $objectId is provided, only attachments for that object are returned.
	 */
	public function findAll(string $objectType, ?int $objectId = null): array {
		return $this->mapper->findAll($objectId ?? 0, $objectType);
	}

	/**
	 * Find all active attachments for a specific object.
	 */
	public function findByObject(int $objectId, string $objectType): array {
		return $this->mapper->findByObject($objectType, $objectId);
	}

	/**
	 * Find a single attachment by id.
	 */
	public function find(int $id): cc_attachment {
		return $this->mapper->find($id);
	}

	/**
	 * Resolve the mapper used for permission checks for a given object type.
	 */
	private function getMapperForObject(string $objectType) {
		switch ($objectType) {
			case 'cc_Case':
				return $this->caseMapper;
			case 'cc_Payment':
				return $this->paymentMapper;
			case 'cc_Update':
				return $this->updateMapper;
		}

		throw new \InvalidArgumentException('Unsupported attachment object type: ' . $objectType);
	}

	/**
	 * Get or create the attachment folder for a specific case.
	 */
	public function getCaseFolder(cc_Case $case): Folder {
		$folder = $this->getCasesFolder();
		$existing = $this->findExistingCaseFolder($folder, $case);
		if ($existing !== null) {
			return $existing;
		}
		return $this->getOrCreateSubFolder($folder, $this->getCaseFolderName($case));
	}

	/**
	 * Get or create the folder for an object's attachments.
	 */
	private function getObjectFolder(string $objectType, int $objectId): Folder {
		$folder = $this->getCasesFolder();
		if ($objectType === 'cc_Case') {
			$case = $this->caseMapper->find($objectId);
			$existing = $this->findExistingCaseFolder($folder, $case);
			if ($existing !== null) {
				return $existing;
			}
			$name = $this->getCaseFolderName($case);
		} else {
			$name = $this->getSubFolderName($objectType, $objectId);
		}
		return $this->getOrCreateSubFolder($folder, $name);
	}

	private function getSubFolderName(string $objectType, int $objectId): string {
		switch ($objectType) {
			case 'cc_Case':
				$case = $this->caseMapper->find($objectId);
				return $this->getCaseFolderName($case);
			case 'cc_Payment':
				return 'Payments/' . sprintf('%010d', $objectId);
			case 'cc_Update':
				return 'Updates/' . sprintf('%010d', $objectId);
		}
		return $objectType . '/' . $objectId;
	}

	/**
	 * Build a folder-safe name for a case attachment directory.
	 */
	private function getCaseFolderName(cc_Case $case): string {
		return sprintf('%010d', $case->getId());
	}

	/**
	 * Sanitize a string for use in folder names.
	 */
	private function sanitizeFolderName(string $name): string {
		$trimmed = trim($name);
		$clean = preg_replace('/[\/:?"<>|]/', '', $trimmed);
		return $clean ?: 'Case';
	}

	/**
	 * Try to find an existing case folder in old naming formats, falling back to new.
	 */
	private function findExistingCaseFolder(Folder $casesFolder, cc_Case $case): ?Folder {
		$newName = $this->getCaseFolderName($case);
		if ($casesFolder->nodeExists($newName)) {
			$node = $casesFolder->get($newName);
			if ($node instanceof Folder) {
				return $node;
			}
		}
		$oldNames = [
			sprintf('%010d', $case->getId()) . ' - ' . $this->sanitizeFolderName($case->getFirstName() . ' ' . $case->getLastName()),
			sprintf('%06d', $case->getId()),
			'Case-' . $case->getId() . '-' . $case->getFirstName() . '-' . $case->getLastName(),
			'Case-' . $case->getId(),
		];
		foreach ($oldNames as $oldName) {
			if ($casesFolder->nodeExists($oldName)) {
				$node = $casesFolder->get($oldName);
				if ($node instanceof Folder) {
					return $node;
				}
			}
		}
		return null;
	}

	/**
	 * Get or create a folder by absolute path.
	 */
	private function getOrCreateFolder(string $path): Folder {
		if ($this->root->nodeExists($path)) {
			$node = $this->root->get($path);
			if (!$node instanceof Folder) {
				throw new \RuntimeException('Path exists but is not a folder: ' . $path);
			}
			return $node;
		}
		return $this->root->newFolder($path);
	}

	/**
	 * Get or create a subfolder relative to a parent folder, supporting nested paths.
	 */
	private function getOrCreateSubFolder(Folder $parent, string $relativePath): Folder {
		$parts = array_filter(explode('/', $relativePath), fn($p) => $p !== '');
		$current = $parent;
		foreach ($parts as $part) {
			if ($current->nodeExists($part)) {
				$node = $current->get($part);
				if (!$node instanceof Folder) {
					throw new \RuntimeException('Path exists but is not a folder: ' . $part);
				}
				$current = $node;
			} else {
				$current = $current->newFolder($part);
			}
		}
		return $current;
	}

	/**
	 * Retrieve a file by name from a folder.
	 */
	private function getFileByName(Folder $folder, string $name): File {
		$file = $folder->get($name);
		if (!$file instanceof File) {
			throw new NotFoundException('File not found: ' . $name);
		}
		return $file;
	}

	/**
	 * Build a URL pointing to the attachment file.
	 */
	private function getFileUrl(string $objectType, int $objectId, int $fileId): string {
		$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$base = strstr($protocol . $host . ($_SERVER['REQUEST_URI'] ?? ''), 'charity', true);
		$groupName = $this->getGroupFolderName($this->getGroupFolderId()) ?? 'Charity';
		$folderName = $this->getSubFolderName($objectType, $objectId);
		return $base . 'files/?dir=/' . $groupName . '/' . $folderName . '&openfile=' . $fileId;
	}
}
