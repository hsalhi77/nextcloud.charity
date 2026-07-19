<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\Helper;
use OCA\Charity\Service\cc_UpdateService;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;

class UpdateController extends Controller {
    private $service;
    private $helper;
    private $UserId;

    public function __construct($AppName, IRequest $request, cc_UpdateService $service, Helper $helper, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->helper = $helper;
        $this->UserId = $UserId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        return new TemplateResponse('charity', 'index');
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
    public function update($id) {
        return $this->helper->handleErrorResponse(function () use ($id) {
            return $this->service->update($this->request->getParams(), $id);
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
