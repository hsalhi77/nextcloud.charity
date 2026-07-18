<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\Helper;
use OCA\Charity\Service\TeamService;
use OCA\Circles\Model\Member;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class TeamController extends Controller {
    private $service;
    private $helper;
    private $UserId;

    public function __construct($AppName, IRequest $request, TeamService $service, Helper $helper, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->helper = $helper;
        $this->UserId = $UserId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function addMember() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams()['params'] ?? [];
            $circleId = $params['circleId'] ?? '';
            $userId = $params['userId'] ?? '';
            $level = (int)($params['level'] ?? Member::LEVEL_MEMBER);
            return $this->service->addMember($circleId, $userId, $level);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteMember() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams()['params'] ?? [];
            $circleId = $params['circleId'] ?? '';
            $memberId = $params['memberId'] ?? '';
            return $this->service->deleteMember($circleId, $memberId);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getCircleMembers() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams()['params'] ?? [];
            return $this->service->getCircleMembers($params['circleId'] ?? '');
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function searchUsers() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams()['params'] ?? [];
            return $this->service->searchUsers($params['search'] ?? '');
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function usersByGroup() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams()['params'] ?? [];
            $groupName = $params['group'] ?? 'Charity';
            return $this->service->getUsersByGroupFiltered($groupName);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function userGroups() {
        return $this->helper->handleErrorResponse(function () {
            return $this->service->getUserGroups();
        });
    }
}
