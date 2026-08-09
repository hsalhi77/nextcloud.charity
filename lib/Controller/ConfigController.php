<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\Helper;
use OCA\Charity\Exceptions\BadRequestException;
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
				'defaultPageSize' => (int)$this->config->getAppValue('charity', 'defaultPageSize', '20'),
				'defaultSortOrder' => $this->config->getAppValue('charity', 'defaultSortOrder', 'newest'),
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
		$allowed = ['createTeamForCase', 'groupLimit', 'groupFolderId', 'defaultPageSize', 'defaultSortOrder'];
		if (!in_array($key, $allowed, true)) {
			return new JSONResponse(['message' => 'Invalid config key', 'data' => []], Http::STATUS_BAD_REQUEST);
		}

		return $this->helper->handleErrorResponse(function () use ($key) {
			$params = $this->request->getParams();
			$value = $params['value'] ?? '';

			if ($key === 'defaultPageSize') {
				if (!ctype_digit((string)$value) || (int)$value < 1 || (int)$value > 100) {
					throw new BadRequestException('Invalid page size');
				}
				$value = (string)(int)$value;
			}

			if ($key === 'defaultSortOrder') {
				if (!in_array($value, ['newest', 'oldest'], true)) {
					throw new BadRequestException('Invalid sort order');
				}
			}

			$this->config->setAppValue('charity', $key, (string)$value);
			return true;
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function prefsGet() {
		return $this->helper->handleErrorResponse(function () {
			$sorts = json_decode($this->config->getUserValue($this->userId, 'charity', 'sorts', '{}'), true);
			return [
				'recordsPerPage' => $this->config->getUserValue($this->userId, 'charity', 'recordsPerPage', ''),
				'sorts' => is_array($sorts) ? $sorts : [],
			];
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function prefsSave() {
		return $this->helper->handleErrorResponse(function () {
			$params = $this->request->getParams();

			if (array_key_exists('recordsPerPage', $params)) {
				$value = (string)($params['recordsPerPage'] ?? '');
				if ($value !== '' && (!ctype_digit($value) || (int)$value < 1 || (int)$value > 100)) {
					throw new BadRequestException('Invalid page size');
				}
				$this->config->setUserValue($this->userId, 'charity', 'recordsPerPage', $value);
			}

			if (array_key_exists('sorts', $params)) {
				$sorts = $params['sorts'];
				if (!is_array($sorts)) {
					throw new BadRequestException('Invalid sorts');
				}
				$clean = [];
				foreach ($sorts as $grid => $sort) {
					if (!is_array($sort) || !is_string($grid) || empty($sort['key']) || !in_array($sort['direction'], ['asc', 'desc'], true)) {
						continue;
					}
					$clean[$grid] = ['key' => (string)$sort['key'], 'direction' => $sort['direction']];
				}
				$this->config->setUserValue($this->userId, 'charity', 'sorts', json_encode($clean));
			}

			return true;
		});
	}
}
