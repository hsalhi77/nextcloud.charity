<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\AttachmentService;
use OCA\Charity\Service\Helper;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class AttachmentController extends Controller {
    private $service;
    private $helper;
    private $UserId;

    public function __construct($AppName, IRequest $request, AttachmentService $service, Helper $helper, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->helper = $helper;
        $this->UserId = $UserId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $object_type
     */
    public function index(string $object_type) {
        return $this->helper->handleErrorResponse(function () use ($object_type) {
            return $this->service->findAll($object_type);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param int $object_id
     * @param string $object_type
     */
    public function show(int $object_id, string $object_type) {
        return $this->helper->handleErrorResponse(function () use ($object_id, $object_type) {
            return $this->service->findAll($object_type, $object_id);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams();
            $objectType = $params['objectType'] ?? '';
            $objectId = (int)($params['objectId'] ?? 0);
            $file = $params['file'] ?? $params;
            return $this->service->create($objectType, $objectId, $file, $this->UserId);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param int $id
     */
    public function destroy(int $id) {
        return $this->helper->handleErrorResponse(function () use ($id) {
            $this->service->delete($id, $this->UserId);
            return true;
        });
    }
}
