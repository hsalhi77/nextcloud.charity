<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\Helper;
use OCA\Charity\Service\cc_CaseTypeService;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class CaseTypeController extends Controller {
    private $service;
    private $helper;
    private $UserId;

    public function __construct($AppName, IRequest $request, cc_CaseTypeService $service, Helper $helper, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->helper = $helper;
        $this->UserId = $UserId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getall() {
        return $this->helper->handleErrorResponse(function () {
            return $this->service->findAll();
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams();
            return $this->service->create($params['title'] ?? '');
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function update($id) {
        return $this->helper->handleErrorResponse(function () use ($id) {
            $params = $this->request->getParams();
            return $this->service->update($id, $params['title'] ?? '');
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
