<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\AclService;
use OCA\Charity\Service\Helper;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class AclController extends Controller {
    private $service;
    private $helper;
    private $UserId;

    public function __construct($AppName, IRequest $request, AclService $service, Helper $helper, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->helper = $helper;
        $this->UserId = $UserId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $object_type
     * @param int $object_id
     */
    public function byObject(string $object_type, int $object_id) {
        return $this->helper->handleErrorResponse(function () use ($object_type, $object_id) {
            return $this->service->findAll($object_type, $object_id);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function addAcl() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams();
            $object_type = $params['object_type'] ?? ($params['objectType'] ?? '');
            $object_id = (int)($params['object_id'] ?? ($params['objectId'] ?? 0));
            $type = $params['type'] ?? null;
            $participant = $params['participant'] ?? null;
            $permissionEdit = $params['permissionEdit'] ?? null;
            $permissionShare = $params['permissionShare'] ?? null;
            $permissionManage = $params['permissionManage'] ?? null;

            return $this->service->addAcl(
                $object_type,
                $object_id,
                $this->UserId,
                $type,
                $participant,
                $permissionEdit,
                $permissionShare,
                $permissionManage
            );
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param $aclId
     */
    public function deleteAcl($aclId) {
        return $this->helper->handleErrorResponse(function () use ($aclId) {
            return $this->service->deleteAcl($aclId, $this->UserId);
        });
    }
}
