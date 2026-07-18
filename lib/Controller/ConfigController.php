<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\Helper;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

class ConfigController extends Controller {
	private $config;
	private $helper;
	private $userId;
	private $db;

	public function __construct($AppName, IRequest $request, IConfig $config, Helper $helper, IDBConnection $db, $userId) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->helper = $helper;
		$this->db = $db;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function get() {
		return $this->helper->handleErrorResponse(function () {
			return [
				'createTeamForCase' => $this->config->getAppValue('charity', 'createTeamForCase', '1') === '1',
				'groupLimit' => $this->config->getAppValue('charity', 'groupLimit', ''),
				'groupFolderId' => $this->config->getAppValue('charity', 'groupFolderId', '1'),
			];
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function groupFolders() {
		return $this->helper->handleErrorResponse(function () {
			$sql = 'SELECT folder_id, mount_point FROM oc_group_folders ORDER BY mount_point';
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$rows = $stmt->fetchAll();
			$folders = [];
			foreach ($rows as $row) {
				$folders[] = [
					'id' => (string)$row['folder_id'],
					'name' => $row['mount_point'],
				];
			}
			return $folders;
		});
	}

	/**
	 * @NoCSRFRequired
	 */
	public function setValue(string $key) {
		$allowed = ['createTeamForCase', 'groupLimit', 'groupFolderId'];
		if (!in_array($key, $allowed, true)) {
			return new JSONResponse(['message' => 'Invalid config key', 'data' => []], Http::STATUS_BAD_REQUEST);
		}

		return $this->helper->handleErrorResponse(function () use ($key) {
			$params = $this->request->getParams();
			$value = $params['value'] ?? '';
			$this->config->setAppValue('charity', $key, (string)$value);
			return true;
		});
	}
}
