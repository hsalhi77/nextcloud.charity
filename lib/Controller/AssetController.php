<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\cc_AssetService;
use OCA\Charity\Service\cc_CaseService;
use OCA\Charity\Service\cc_PaymentService;
use OCA\Charity\Service\Helper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IRequest;

class AssetController extends Controller {
	private cc_AssetService $assetService;
	private cc_PaymentService $paymentService;
	private cc_CaseService $caseService;
	private Helper $helper;

	public function __construct($AppName, IRequest $request, cc_AssetService $assetService, cc_PaymentService $paymentService, cc_CaseService $caseService, Helper $helper) {
		parent::__construct($AppName, $request);
		$this->assetService = $assetService;
		$this->paymentService = $paymentService;
		$this->caseService = $caseService;
		$this->helper = $helper;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getall() {
		return $this->helper->handleErrorResponse(function () {
			return $this->assetService->findAll($this->request->getParams());
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function show($id) {
		return $this->helper->handleErrorResponse(function () use ($id) {
			$asset = $this->assetService->find($id);
			if ($asset === null) {
				throw new DoesNotExistException('Asset not found');
			}
			return $asset;
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function create() {
		return $this->helper->handleErrorResponse(function () {
			return $this->assetService->create($this->request->getParams());
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function update($id) {
		return $this->helper->handleErrorResponse(function () use ($id) {
			return $this->assetService->update((int)$id, $this->request->getParams());
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function delete($id) {
		return $this->helper->handleErrorResponse(function () use ($id) {
			return $this->assetService->delete((int)$id);
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function byPayment() {
		return $this->helper->handleErrorResponse(function () {
			$paymentId = (int)$this->request->getParam('paymentId');
			// Ensures the payment exists and the caller may access it.
			$this->paymentService->find($paymentId);
			return $this->assetService->findByPayment($paymentId);
		});
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function byCase() {
		return $this->helper->handleErrorResponse(function () {
			$caseId = (int)$this->request->getParam('caseId');
			// Ensures the case exists and the caller may access it.
			$this->caseService->find($caseId);
			return $this->assetService->findByCase($caseId);
		});
	}
}
