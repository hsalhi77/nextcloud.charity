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
		$this->logger->info('Charity attachment create start', [
			'app' => 'charity',
			'objectType' => $objectType,
			'objectId' => $objectId,
			'fileName' => $file['name'] ?? null,
			'fileSize' => $file['size'] ?? null,
			'hasFileData' => !empty($file['data']),
			'userId' => $userId,
		]);
		try {
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

			$tmpName = $file['tmp_name'] ?? '';
			if (!empty($tmpName) && is_uploaded_file($tmpName)) {
				$tmpSize = filesize($tmpName);
				$this->logger->info('Charity attachment reading uploaded file', [
					'app' => 'charity',
					'tmpName' => $tmpName,
					'tmpSize' => $tmpSize,
				]);
				if ($tmpSize === 0) {
					throw new \InvalidArgumentException('Uploaded file is empty (upload may have been truncated by the web server)');
				}
				$base = file_get_contents($tmpName);
				if ($base === false) {
					throw new \RuntimeException('Failed to read uploaded file');
				}
			} else {
				$base = $file['data'] ?? '';
				if (strpos($base, ',') !== false) {
					$base = explode(',', $base, 2)[1];
				}
				$decoded = base64_decode($base, true);
				if ($decoded === false) {
					throw new \InvalidArgumentException('Invalid base64 data for attachment');
				}
				$base = $decoded;
			}

			$filename = str_replace([':', '-', ' '], '_', date('Y-m-d H:i:s')) . '_' . ($file['name'] ?? 'unnamed');
			$attachment->setName($filename);

			$folder = $this->getObjectFolder($objectType, $objectId);
			$fileNode = $folder->newFile($filename);
			$fileNode->putContent($base);

			$attachment->setUrl($this->getFileUrl($objectType, $objectId, $fileNode->getId()));

			$result = $this->mapper->insert($attachment);
			$this->logger->info('Charity attachment create success', [
				'app' => 'charity',
				'attachmentId' => $result->getId(),
				'fileName' => $filename,
			]);
			return $result;
		} catch (\Throwable $e) {
			$this->logger->error('Charity attachment create failed: ' . $e->getMessage(), [
				'app' => 'charity',
				'objectType' => $objectType,
				'objectId' => $objectId,
				'fileName' => $file['name'] ?? null,
				'fileSize' => $file['size'] ?? null,
				'exceptionClass' => get_class($e),
				'trace' => $e->getTraceAsString(),
			]);
			throw $e;
		}
	}

	/**
	 * Get the temporary directory path for a chunked upload.
	 */
	private function getChunkDir(string $uploadId): string {
		$base = sys_get_temp_dir() . '/charity_upload_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId);
		if (!is_dir($base)) {
			mkdir($base, 0700, true);
		}
		return $base;
	}

	/**
	 * Store a single chunk of a chunked upload.
	 */
	public function storeChunk(string $uploadId, int $index, int $total, ?array $uploadedFile): void {
		if ($uploadId === '' || $total <= 0 || $index < 0 || $index >= $total) {
			throw new \InvalidArgumentException('Invalid chunk upload parameters');
		}
		$uploadedFile = $uploadedFile ?? [];
		$tmpName = $uploadedFile['tmp_name'] ?? '';
		$error = (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
		if ($error !== UPLOAD_ERR_OK || empty($tmpName) || !is_uploaded_file($tmpName)) {
			$this->logger->error('Charity attachment chunk upload failed', [
				'app' => 'charity',
				'uploadId' => $uploadId,
				'index' => $index,
				'error' => $error,
				'tmpName' => $tmpName,
			]);
			throw new \InvalidArgumentException('Chunk upload failed with error code ' . $error);
		}
		$chunkDir = $this->getChunkDir($uploadId);
		if (!is_dir($chunkDir) || !is_writable($chunkDir)) {
			throw new \RuntimeException('Chunk directory is not writable: ' . $chunkDir);
		}
		$target = $chunkDir . '/' . $index;
		if (!move_uploaded_file($tmpName, $target)) {
			$error = error_get_last();
			$this->logger->error('Charity attachment move_uploaded_file failed', [
				'app' => 'charity',
				'uploadId' => $uploadId,
				'index' => $index,
				'source' => $tmpName,
				'target' => $target,
				'lastError' => $error,
			]);
			throw new \RuntimeException('Failed to store upload chunk');
		}
		$this->logger->info('Charity attachment chunk stored', [
			'app' => 'charity',
			'uploadId' => $uploadId,
			'index' => $index,
			'total' => $total,
		]);
	}

	/**
	 * Finalize a chunked upload: reassemble chunks, write the file, and create the attachment record.
	 */
	public function finalizeUpload(string $objectType, int $objectId, string $uploadId, string $filename, string $tag, string $description, int $total, string $userId): cc_attachment {
		$this->logger->info('Charity attachment finalize start', [
			'app' => 'charity',
			'objectType' => $objectType,
			'objectId' => $objectId,
			'uploadId' => $uploadId,
			'filename' => $filename,
			'total' => $total,
			'userId' => $userId,
		]);
		try {
			if ($uploadId === '' || $filename === '' || $total <= 0) {
				throw new \InvalidArgumentException('Missing upload id, filename or total chunks');
			}
			$mapper = $this->getMapperForObject($objectType);
			$object = $mapper->find($objectId);

			$permObjectType = $objectType;
			$permObjectId = $objectId;
			if (($objectType === 'cc_Payment' || $objectType === 'cc_Update') && $object->getCaseId()) {
				$permObjectType = 'cc_Case';
				$permObjectId = $object->getCaseId();
			}
			$this->permissionService->checkPermission($this->caseMapper, $permObjectType, $permObjectId, Acl::PERMISSION_READ);

			$chunkDir = $this->getChunkDir($uploadId);
			$chunks = glob($chunkDir . '/*');
			sort($chunks, SORT_NATURAL);
			if (count($chunks) !== $total) {
				throw new \InvalidArgumentException('Expected ' . $total . ' chunks but found ' . count($chunks));
			}
			$expectedChunks = [];
			for ($i = 0; $i < $total; $i++) {
				$expectedChunks[] = $chunkDir . '/' . $i;
			}
			if ($chunks !== $expectedChunks) {
				throw new \InvalidArgumentException('Missing or unexpected upload chunks');
			}

			$tmpFile = tempnam(sys_get_temp_dir(), 'charity_finalize_');
			$out = fopen($tmpFile, 'wb');
			if (!$out) {
				throw new \RuntimeException('Failed to create temporary file for reassembly');
			}
			$totalSize = 0;
			foreach ($chunks as $chunk) {
				$in = fopen($chunk, 'rb');
				if (!$in) {
					fclose($out);
					unlink($tmpFile);
					throw new \RuntimeException('Failed to read chunk');
				}
				$bytes = stream_copy_to_stream($in, $out);
				fclose($in);
				if ($bytes === false) {
					fclose($out);
					unlink($tmpFile);
					throw new \RuntimeException('Failed to copy chunk');
				}
				$totalSize += $bytes;
			}
			fclose($out);

			foreach ($chunks as $chunk) {
				unlink($chunk);
			}
			rmdir($chunkDir);

			if ($totalSize === 0) {
				unlink($tmpFile);
				throw new \InvalidArgumentException('Reassembled file is empty');
			}

			$safeFilename = str_replace([':', '-', ' '], '_', date('Y-m-d H:i:s')) . '_' . $filename;

		$folder = $this->getObjectFolder($objectType, $objectId);
		$fileNode = $folder->newFile($safeFilename);
		$stream = fopen($tmpFile, 'rb');
		if (!$stream) {
			unlink($tmpFile);
			throw new \RuntimeException('Failed to open reassembled file');
		}
		try {
			$fileNode->putContent($stream);
		} finally {
			if (is_resource($stream)) {
				fclose($stream);
			}
			unlink($tmpFile);
		}

			$attachment = new cc_attachment();
			$attachment->setObjectId($objectId);
			$attachment->setObjectType($objectType);
			$attachment->setCreated(date('Y-m-d H:i:s'));
			$attachment->setUpdated(date('Y-m-d H:i:s'));
			$attachment->setIsactive(true);
			$attachment->setTag($tag);
			$attachment->setDescription($description);
			$attachment->setData($filename);
			$attachment->setSize($totalSize);
			$attachment->setName($safeFilename);
			$attachment->setUrl($this->getFileUrl($objectType, $objectId, $fileNode->getId()));

			$result = $this->mapper->insert($attachment);
			$this->logger->info('Charity attachment finalize success', [
				'app' => 'charity',
				'attachmentId' => $result->getId(),
				'fileName' => $safeFilename,
				'size' => $totalSize,
			]);
			return $result;
		} catch (\Throwable $e) {
			$this->logger->error('Charity attachment finalize failed: ' . $e->getMessage(), [
				'app' => 'charity',
				'objectType' => $objectType,
				'objectId' => $objectId,
				'uploadId' => $uploadId,
				'filename' => $filename,
				'exceptionClass' => get_class($e),
				'trace' => $e->getTraceAsString(),
			]);
			$chunkDir = $this->getChunkDir($uploadId);
			if (is_dir($chunkDir)) {
				array_map('unlink', glob($chunkDir . '/*'));
				rmdir($chunkDir);
			}
			throw $e;
		}
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
