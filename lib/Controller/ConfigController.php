<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\Helper;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class ConfigController extends Controller {
	private $config;
	private $helper;
	private $userId;

	public function __construct($AppName, IRequest $request, IConfig $config, Helper $helper, $userId) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->helper = $helper;
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
			];
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function setValue($key) {
		return $this->helper->handleErrorResponse(function () use ($key) {
			$params = $this->request->getParams();
			$value = $params['value'] ?? '';
			$this->config->setAppValue('charity', $key, (string)$value);
			return true;
		});
	}
}
