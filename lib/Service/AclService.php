<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\Acl;
use OCA\Charity\Db\AclMapper;
use OCA\Charity\Db\cc_CaseMapper;
use OCA\Charity\Exceptions\BadRequestException;
use OCA\Charity\Exceptions\NoPermissionException;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\ISession;

class AclService {
	private $caseMapper;
	private $aclMapper;
	private $permissionService;
	private $userManager;
	private $groupManager;
	private $userId;
	private $session;

	public function __construct(
		cc_CaseMapper $caseMapper,
		AclMapper $aclMapper,
		PermissionService $permissionService,
		IUserManager $userManager,
		IGroupManager $groupManager,
		ISession $session,
		$userId
	) {
		$this->caseMapper = $caseMapper;
		$this->aclMapper = $aclMapper;
		$this->permissionService = $permissionService;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->session = $session;
		$this->userId = $userId;
	}

	public function findAll(string $object_type, int $object_id) {
		$acls = $this->aclMapper->findAll($object_type, $object_id);
		$userManager = $this->userManager;
		$groupManager = $this->groupManager;
		foreach ($acls as &$acl) {
			$acl->resolveRelation('participant', function ($participant) use (&$acl, &$userManager, &$groupManager) {
				if ($acl->getType() === Acl::PERMISSION_TYPE_USER) {
					$user = $userManager->get($participant);
					if ($user !== null) {
						return new \OCA\Charity\Db\User($user);
					}
					return null;
				}
				if ($acl->getType() === Acl::PERMISSION_TYPE_GROUP) {
					$group = $groupManager->get($participant);
					if ($group !== null) {
						return new \OCA\Charity\Db\Group($group);
					}
					return null;
				}
				if ($acl->getType() === Acl::PERMISSION_TYPE_CIRCLE) {
					try {
						$circle = \OCA\Circles\Api\v1\Circles::detailsCircle($participant, true);
						if ($circle !== null) {
							return new \OCA\Charity\Db\Circle($circle);
						}
					} catch (\Exception $e) {
					}
					return null;
				}
				return null;
			});
		}
		return $acls;
	}

	public function addAcl(string $object_type, int $object_id, string $userId, $type, $participant, $edit, $share, $manage, $parentid = 0) {
		if (is_numeric($object_id) === false) {
			throw new BadRequestException('object id must be a number');
		}

		if ($object_type === false || $object_type === null) {
			throw new BadRequestException('object type must be provided');
		}

		if ($type === false || $type === null) {
			throw new BadRequestException('type must be provided');
		}

		if ($participant === false || $participant === null) {
			throw new BadRequestException('participant must be provided');
		}

		if ($edit === null) {
			throw new BadRequestException('edit must be provided');
		}

		if ($share === null) {
			throw new BadRequestException('share must be provided');
		}

		if ($manage === null) {
			throw new BadRequestException('manage must be provided');
		}

		if ($object_type === 'cc_Case') {
			$this->permissionService->checkPermission($this->caseMapper, $object_type, $object_id, Acl::PERMISSION_SHARE);
		}

		[$edit, $share, $manage] = $this->applyPermissions($object_type, $object_id, $edit, $share, $manage);

		$acl = new Acl();
		$acl->setobjectId($object_id);
		$acl->setdescription($object_type);
		$acl->setobjectType($object_type);
		$acl->setType($type);
		$acl->setparentid($parentid);
		$acl->setParticipant($participant);
		$acl->setPermissionEdit((bool)$edit);
		$acl->setPermissionShare((bool)$share);
		$acl->setPermissionManage((bool)$manage);

		return $this->aclMapper->insert($acl);
	}

	public function deleteAcl($id, string $userId) {
		if (is_numeric($id) === false) {
			throw new BadRequestException('id must be a number');
		}
		$acl = $this->aclMapper->find($id);
		if ($acl === null) {
			return;
		}
		if ($acl->getobjectType() === 'cc_Case') {
			$this->permissionService->checkPermission($this->caseMapper, 'cc_Case', $acl->getobjectId(), Acl::PERMISSION_MANAGE);
		}
		return $this->aclMapper->delete($acl);
	}

	private function applyPermissions($object_type, $object_id, $edit, $share, $manage) {
		try {
			if ($object_type === 'cc_Case') {
				$this->permissionService->checkPermission($this->caseMapper, $object_type, $object_id, Acl::PERMISSION_MANAGE);
			}
		} catch (NoPermissionException $e) {
			$acls = $this->aclMapper->findAll($object_type, $object_id);
			$edit = $this->permissionService->userCan($acls, Acl::PERMISSION_EDIT, $this->userId) && $edit;
			$share = $this->permissionService->userCan($acls, Acl::PERMISSION_SHARE, $this->userId) && $share;
			$manage = $this->permissionService->userCan($acls, Acl::PERMISSION_MANAGE, $this->userId) && $manage;
		}
		return [$edit, $share, $manage];
	}
}
