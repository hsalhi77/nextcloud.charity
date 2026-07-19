<?php
declare(strict_types=1);
namespace OCA\Charity\Setup;

use OCP\App\IAppManager;
use OCP\Constants;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class AppSetup {
	public const GROUP_ADMIN = 'Charity Admin';
	public const GROUP_USER = 'Charity User';
	public const GROUP_FIELD = 'Charity Field';
	public const GROUP_FOLDER_NAME = 'Charity';
	public const SETUP_FLAG = 'setup_complete';

	private IGroupManager $groupManager;
	private IConfig $config;
	private IAppManager $appManager;
	private LoggerInterface $logger;

	public function __construct(IGroupManager $groupManager, IConfig $config, IAppManager $appManager, LoggerInterface $logger) {
		$this->groupManager = $groupManager;
		$this->config = $config;
		$this->appManager = $appManager;
		$this->logger = $logger;
	}

	public function run(): void {
		if ($this->config->getAppValue('charity', self::SETUP_FLAG, '0') === '1') {
			$this->logger->debug('Charity setup already completed, skipping');
			return;
		}

		$this->logger->info('Running Charity app setup');

		$this->createGroups();

		if (!$this->appManager->isEnabledForUser('groupfolders')) {
			$this->logger->warning('Group Folders app is not enabled. Charity setup will retry on next app enable.');
			return;
		}

		$folderId = $this->ensureGroupFolder();
		if ($folderId === null) {
			$this->logger->error('Could not create or find Charity group folder');
			return;
		}

		$this->config->setAppValue('charity', 'groupFolderId', (string)$folderId);
		$this->config->setAppValue('charity', self::SETUP_FLAG, '1');

		$this->logger->info('Charity app setup completed', ['groupFolderId' => $folderId]);
	}

	private function createGroups(): void {
		foreach ([self::GROUP_ADMIN, self::GROUP_USER, self::GROUP_FIELD] as $groupId) {
			if ($this->groupManager->groupExists($groupId)) {
				$this->logger->debug('Charity group already exists', ['group' => $groupId]);
				continue;
			}
			$group = $this->groupManager->createGroup($groupId);
			if ($group !== null) {
				$this->logger->info('Created Charity group', ['group' => $groupId]);
			} else {
				$this->logger->warning('Could not create Charity group', ['group' => $groupId]);
			}
		}
	}

	private function ensureGroupFolder(): ?int {
		if (!class_exists(\OCA\GroupFolders\Folder\FolderManager::class)) {
			$this->logger->error('Group Folders FolderManager class is not available');
			return null;
		}

		try {
			$folderManager = Server::get(\OCA\GroupFolders\Folder\FolderManager::class);
		} catch (\Throwable $e) {
			$this->logger->error('Could not load Group Folders FolderManager', ['exception' => $e->getMessage()]);
			return null;
		}

		// Look for an existing folder with the same mount point
		foreach ($folderManager->getAllFolders() as $folder) {
			if ($folder->mountPoint === self::GROUP_FOLDER_NAME) {
				$this->logger->info('Using existing Charity group folder', ['folderId' => $folder->id]);
				$this->ensureGroupAccess($folderManager, $folder->id, $folder->groups);
				return $folder->id;
			}
		}

		try {
			$folderId = $folderManager->createFolder(self::GROUP_FOLDER_NAME);
			$this->logger->info('Created Charity group folder', ['folderId' => $folderId]);
			$this->ensureGroupAccess($folderManager, $folderId, []);
			return $folderId;
		} catch (\Throwable $e) {
			$this->logger->error('Failed to create Charity group folder', ['exception' => $e->getMessage()]);
			return null;
		}
	}

	private function ensureGroupAccess(\OCA\GroupFolders\Folder\FolderManager $folderManager, int $folderId, array $existingGroups): void {
		foreach ([self::GROUP_ADMIN, self::GROUP_USER, self::GROUP_FIELD] as $groupId) {
			if (isset($existingGroups[$groupId])) {
				$this->logger->debug('Group already has access to Charity folder', ['group' => $groupId]);
				continue;
			}
			try {
				$folderManager->addApplicableGroup($folderId, $groupId);
				$this->logger->info('Granted Charity folder access to group', ['group' => $groupId]);
			} catch (\Throwable $e) {
				$this->logger->warning('Could not grant Charity folder access to group', ['group' => $groupId, 'exception' => $e->getMessage()]);
			}
		}
	}
}
