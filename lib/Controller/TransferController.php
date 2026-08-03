<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\Helper;
use OCA\Charity\Service\cc_TransferService;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class TransferController extends Controller {
    private $service;
    private $helper;

    public function __construct($AppName, IRequest $request, cc_TransferService $service, Helper $helper) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->helper = $helper;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getall() {
        return $this->helper->handleErrorResponse(function () {
            return $this->service->findAll($this->request->getParams());
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show($id) {
        return $this->helper->handleErrorResponse(function () use ($id) {
            return $this->service->find($id);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create() {
        return $this->helper->handleErrorResponse(function () {
            return $this->service->create($this->request->getParams());
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function destroy($id) {
        return $this->helper->handleErrorResponse(function () use ($id) {
            return $this->service->delete($id);
        });
    }
}
