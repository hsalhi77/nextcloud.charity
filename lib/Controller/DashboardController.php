<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\DashboardService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class DashboardController extends Controller {
	private DashboardService $dashboardService;

	public function __construct($AppName, IRequest $request, DashboardService $dashboardService) {
		parent::__construct($AppName, $request);
		$this->dashboardService = $dashboardService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function stats(): JSONResponse {
		$stats = $this->dashboardService->getStats();
		$stats['payoutRatio'] = $stats['totalReceipts'] > 0
			? $stats['totalPayments'] / $stats['totalReceipts']
			: 0;
		return new JSONResponse($stats);
	}
}
