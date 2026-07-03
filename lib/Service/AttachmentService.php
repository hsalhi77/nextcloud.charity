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
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;

class AttachmentService {
	private cc_attachmentMapper $mapper;
	private cc_CaseMapper $caseMapper;
	private cc_PaymentMapper $paymentMapper;
	private cc_UpdateMapper $updateMapper;
	private PermissionService $permissionService;
	private IRootFolder $root;
	private ?string $userId;

	public function __construct(
		cc_attachmentMapper $mapper,
		cc_CaseMapper $caseMapper,
		cc_PaymentMapper $paymentMapper,
		cc_UpdateMapper $updateMapper,
		PermissionService $permissionService,
		IRootFolder $root,
		$userId
	) {
		$this->mapper = $mapper;
		$this->caseMapper = $caseMapper;
		$this->paymentMapper = $paymentMapper;
		$this->updateMapper = $updateMapper;
		$this->permissionService = $permissionService;
		$this->root = $root;
		$this->userId = $userId;
	}

	/**
	 * Get or create the Charity folder for the given user.
	 */
	public function getCasesFolder(string $userId): Folder {
		$userFolder = $this->root->getUserFolder($userId);
		$path = $userFolder->getPath() . '/Charity';
		return $this->getOrCreateFolder($path);
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

		$folder = $this->getObjectFolder($userId, $objectType, $objectId);
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

		$folder = $this->getObjectFolder($userId ?? $this->userId, $objectType, $objectId);
		try {
			$file = $this->getFileByName($folder, $attachment->getName());
			if ($file->isDeletable()) {
				$file->delete();
			}
		} catch (NotFoundException $e) {
			\OC::$server->getLogger()->warning('Charity: Attachment file not found for deletion: ' . $attachment->getName(), ['app' => 'charity']);
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
	public function deleteCaseFolder(int $caseId, string $userId): void {
		$case = $this->caseMapper->find($caseId);
		if ($case === null) {
			return;
		}
		$folder = $this->getCasesFolder($userId);
		$path = $folder->getPath() . '/' . $this->getCaseFolderName($case);
		$this->deleteFolderByPath($path);
	}

	/**
	 * Delete an object's attachment folder (payments, updates, etc.).
	 */
	public function deleteObjectFolder(int $objectId, string $objectType, string $userId): void {
		$folder = $this->getCasesFolder($userId);
		$path = $folder->getPath() . '/' . $this->getSubFolderName($objectType, $objectId);
		$this->deleteFolderByPath($path);
	}

	private function deleteFolderByPath(string $path): void {
		try {
			if ($this->root->nodeExists($path)) {
				$node = $this->root->get($path);
				if ($node instanceof Folder && $node->isDeletable()) {
					$node->delete();
				}
			}
		} catch (\OCP\Files\NotFoundException $e) {
			\OC::$server->getLogger()->warning('Charity: Folder not found for deletion: ' . $path, ['app' => 'charity']);
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
	public function getCaseFolder(cc_Case $case, string $userId): Folder {
		$folder = $this->getCasesFolder($userId);
		$existing = $this->findExistingCaseFolder($folder, $case);
		if ($existing !== null) {
			return $existing;
		}
		$path = $folder->getPath() . '/' . $this->getCaseFolderName($case);
		return $this->getOrCreateFolder($path);
	}

	/**
	 * Get or create the folder for an object's attachments.
	 */
	private function getObjectFolder(string $userId, string $objectType, int $objectId): Folder {
		$folder = $this->getCasesFolder($userId);
		if ($objectType === 'cc_Case') {
			$case = $this->caseMapper->find($objectId);
			$existing = $this->findExistingCaseFolder($folder, $case);
			if ($existing !== null) {
				return $existing;
			}
			$path = $folder->getPath() . '/' . $this->getSubFolderName($objectType, $objectId);
		} else {
			$path = $folder->getPath() . '/' . $this->getSubFolderName($objectType, $objectId);
		}
		return $this->getOrCreateFolder($path);
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
		$folderName = $this->getSubFolderName($objectType, $objectId);
		return $base . 'files/?dir=/Charity/' . $folderName . '&openfile=' . $fileId;
	}
}
