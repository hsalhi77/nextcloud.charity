<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_Case;
use OCA\Charity\Db\cc_CaseMapper;
use OCA\Charity\Db\Acl;
use OCA\Charity\Exceptions\NoPermissionException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\ISession;

class cc_CaseService {
	private $mapper;
	private $aclService;
	private $permissionService;
	private $teamService;
	private $attachmentService;
	private $userManager;
	private $groupManager;
	private $userId;
	private $session;

	public function __construct(
		cc_CaseMapper $mapper,
		AclService $aclService,
		PermissionService $permissionService,
		TeamService $teamService,
		AttachmentService $attachmentService,
		IUserManager $userManager,
		IGroupManager $groupManager,
		ISession $session,
		$userId
	) {
		$this->mapper = $mapper;
		$this->aclService = $aclService;
		$this->permissionService = $permissionService;
		$this->teamService = $teamService;
		$this->attachmentService = $attachmentService;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->session = $session;
		$this->userId = $userId;
	}

	public function findAll($param = []) {
		$userInfo = $this->getPrerequisites();
		$userItems = $this->mapper->findAllByUser($userInfo['user'], $param);
		$result = [];
		foreach ($userItems as &$item) {
			if ($item === null) continue;
			if (!array_key_exists($item->getId(), $result)) {
				$this->mapper->mapOwner($item);
				$permissions = $this->permissionService->matchPermissions('cc_Case', $item->getId());
				$item->setPermissions([
					'PERMISSION_READ' => $permissions[Acl::PERMISSION_READ] ?? false,
					'PERMISSION_EDIT' => $permissions[Acl::PERMISSION_EDIT] ?? false,
					'PERMISSION_MANAGE' => $permissions[Acl::PERMISSION_MANAGE] ?? false,
					'PERMISSION_SHARE' => $permissions[Acl::PERMISSION_SHARE] ?? false,
				]);
				$result[$item->getId()] = $item;
			}
		}
		return array_values($result);
	}

	public function find($id) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Case not found');
		}
		$this->mapper->mapOwner($item);
		$permissions = $this->permissionService->matchPermissions('cc_Case', $item->getId());
		$item->setPermissions([
			'PERMISSION_READ' => $permissions[Acl::PERMISSION_READ] ?? false,
			'PERMISSION_EDIT' => $permissions[Acl::PERMISSION_EDIT] ?? false,
			'PERMISSION_MANAGE' => $permissions[Acl::PERMISSION_MANAGE] ?? false,
			'PERMISSION_SHARE' => $permissions[Acl::PERMISSION_SHARE] ?? false,
		]);
		return [$item];
	}

	public function create($param) {
		if (!$this->permissionService->canCreate()) {
			throw new NoPermissionException('Creating cases has been disabled.');
		}
		$item = new cc_Case();
		$item->setFirstName($param['firstName'] ?? '');
		$item->setLastName($param['lastName'] ?? '');
		$item->setIdNumber($param['idNumber'] ?? '');
		$item->setCityId($param['cityId'] ?? null);
		$item->setTown($param['town'] ?? '');
		$item->setLocation($param['location'] ?? '');
		$item->setDob(isset($param['dob']) ? new \DateTime($param['dob']) : null);
		$item->setDateAdded(isset($param['dateAdded']) ? new \DateTime($param['dateAdded']) : new \DateTime());
		$item->setDependants($param['dependants'] ?? 0);
		$item->setCaseTypeId($param['caseTypeId'] ?? null);
		$item->setDescription($param['description'] ?? '');
		$item->setRecommendation($param['recommendation'] ?? '');
		$item->setReferredBy($param['referredBy'] ?? $this->userId);
		$item->setOwner($this->userId);
		$item->setIsactive(1);

		$config = \OC::$server->get(\OCP\IConfig::class);
		$createTeam = $config->getAppValue('charity', 'createTeamForCase', '1');
		if ($createTeam === '1') {
			$circleId = $this->teamService->createCaseCircle($param['firstName'] . ' ' . $param['lastName']);
			$item->setCircleId($circleId);
		}

		$item = $this->mapper->insert($item);

		$folder = $this->attachmentService->getCasesFolder($this->userId);
		$folder->newFolder($item->getId());

		if ($item->getCircleId()) {
			$this->aclService->addAcl('cc_Case', $item->getId(), $this->userId, Acl::PERMISSION_TYPE_CIRCLE, $item->getCircleId(), 1, 1, 1);
		}

		$permissions = $this->permissionService->matchPermissions('cc_Case', $item->getId());
		$item->setPermissions([
			'PERMISSION_READ' => $permissions[Acl::PERMISSION_READ] ?? false,
			'PERMISSION_EDIT' => $permissions[Acl::PERMISSION_EDIT] ?? false,
			'PERMISSION_MANAGE' => $permissions[Acl::PERMISSION_MANAGE] ?? false,
			'PERMISSION_SHARE' => $permissions[Acl::PERMISSION_SHARE] ?? false,
		]);
		return $item;
	}

	public function update($param, $id) {
		$this->permissionService->checkPermission($this->mapper, 'cc_Case', $id, Acl::PERMISSION_EDIT);
		$item = $this->mapper->find($id);
		if (isset($param['firstName'])) $item->setFirstName($param['firstName']);
		if (isset($param['lastName'])) $item->setLastName($param['lastName']);
		if (isset($param['idNumber'])) $item->setIdNumber($param['idNumber']);
		if (isset($param['cityId'])) $item->setCityId($param['cityId']);
		if (isset($param['town'])) $item->setTown($param['town']);
		if (isset($param['location'])) $item->setLocation($param['location']);
		if (isset($param['dob'])) $item->setDob(new \DateTime($param['dob']));
		if (isset($param['dateAdded'])) $item->setDateAdded(new \DateTime($param['dateAdded']));
		if (isset($param['dependants'])) $item->setDependants($param['dependants']);
		if (isset($param['caseTypeId'])) $item->setCaseTypeId($param['caseTypeId']);
		if (isset($param['description'])) $item->setDescription($param['description']);
		if (isset($param['recommendation'])) $item->setRecommendation($param['recommendation']);
		if (isset($param['referredBy'])) $item->setReferredBy($param['referredBy']);
		$item->setUpdated(new \DateTime());
		return $this->mapper->update($item);
	}

	public function delete($id) {
		$this->permissionService->checkPermission($this->mapper, 'cc_Case', $id, Acl::PERMISSION_MANAGE);
		$item = $this->mapper->find($id);
		$item->setIsactive(0);
		$this->mapper->update($item);
		return true;
	}

	private function getPrerequisites() {
		$groups = $this->groupManager->getUserGroupIds($this->userManager->get($this->userId));
		return ['user' => $this->userId, 'groups' => $groups];
	}
}
