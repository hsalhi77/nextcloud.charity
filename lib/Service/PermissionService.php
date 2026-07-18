<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Charity\Service;

use OCA\Charity\Db\Acl;
use OCA\Charity\Db\AclMapper;
use OCA\Charity\Db\cc_CaseMapper;
use OCA\Charity\Exceptions\NoPermissionException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;

class PermissionService {
	/** @var cc_CaseMapper */
	private $caseMapper;

	/** @var AclMapper */
	private $aclMapper;

	/** @var LoggerInterface */
	private $logger;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IConfig */
	private $config;

	/** @var IManager */
	private $shareManager;

	/** @var string */
	private $userId;

	/** @var array */
	private $users = [];

	private $circlesEnabled = false;

	public function __construct(
		LoggerInterface $logger,
		AclMapper $aclMapper,
		cc_CaseMapper $caseMapper,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IManager $shareManager,
		IConfig $config,
		$userId
	) {
		$this->aclMapper = $aclMapper;
		$this->caseMapper = $caseMapper;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->shareManager = $shareManager;
		$this->config = $config;
		$this->userId = $userId;

		$this->circlesEnabled = \OC::$server->getAppManager()->isEnabledForUser('circles') &&
			(version_compare(\OC::$server->getAppManager()->getAppVersion('circles'), '0.17.1') >= 0);
	}

	/**
	 * Get current user permissions for a cc_Case by id
	 *
	 * @param $object_type
	 * @param $object_id
	 * @return bool|array
	 */
	public function getPermissions($object_type, $object_id) {
		$owner = $this->userIsOwner($object_type, $object_id);
		$acls = $this->aclMapper->findAll($object_type, $object_id);
		return [
			Acl::PERMISSION_READ => $owner || $this->userCan($acls, Acl::PERMISSION_READ),
			Acl::PERMISSION_EDIT => $owner || $this->userCan($acls, Acl::PERMISSION_EDIT),
			Acl::PERMISSION_MANAGE => $owner || $this->userCan($acls, Acl::PERMISSION_MANAGE),
			Acl::PERMISSION_SHARE => ($owner || $this->userCan($acls, Acl::PERMISSION_SHARE))
				&& (!$this->shareManager->sharingDisabledForUser($this->userId))
		];
	}

	/**
	 * Get current user permissions for a cc_Case
	 *
	 * @param string $objectType
	 * @param int $objectId
	 * @return array|bool
	 */
	public function matchPermissions($objectType, $objectId) {
		return $this->getPermissions($objectType, $objectId);
	}

	/**
	 * Check permissions for replacing dark magic middleware
	 *
	 * @param $mapper
	 * @param $object_type
	 * @param $object_id
	 * @param $permission
	 * @param string|null $userId
	 * @return bool
	 * @throws NoPermissionException
	 */
	public function checkPermission($mapper, $object_type, $object_id, $permission, $userId = null) {
		if ($object_id === null) {
			throw new NoPermissionException('Permission denied');
		}

		if ($permission === Acl::PERMISSION_SHARE && $this->shareManager->sharingDisabledForUser($this->userId)) {
			return false;
		}

		if ($this->userIsOwner($object_type, $object_id, $userId)) {
			return true;
		}

		$acls = $this->aclMapper->findAll($object_type, $object_id);
		$result = $this->userCan($acls, $permission, $userId);
		if ($result) {
			return true;
		}

		throw new NoPermissionException('Permission denied');
	}

	/**
	 * @param string $objectType
	 * @param int $objectId
	 * @param string|null $userId
	 * @return bool
	 */
	public function userIsOwner($objectType, $objectId, $userId = null) {
		if ($userId === null) {
			$userId = $this->userId;
		}
		try {
			if ($objectType === 'cc_Case') {
				$case = $this->caseMapper->find($objectId);
				return $case && $userId === $case->getowner();
			}
		} catch (DoesNotExistException $e) {
		} catch (MultipleObjectsReturnedException $e) {
			return false;
		}
		return false;
	}

	/**
	 * Check if permission matches the acl rules for current user and groups
	 *
	 * @param Acl[] $acls
	 * @param $permission
	 * @param string|null $userId
	 * @return bool
	 */
	public function userCan(array $acls, $permission, $userId = null) {
		if ($userId === null) {
			$userId = $this->userId;
		}
		foreach ($acls as $acl) {
			if ($acl->getType() === Acl::PERMISSION_TYPE_USER && $acl->getParticipant() === $userId) {
				return $acl->getPermission($permission);
			}

			if ($this->circlesEnabled && $acl->getType() === Acl::PERMISSION_TYPE_CIRCLE) {
				try {
					$member = \OCA\Circles\Api\v1\Circles::getMember($acl->getParticipant(), $this->userId, 1, true);
					$level = $member->getLevel();
					$levelRequired = [
						Acl::PERMISSION_READ => 1,
						Acl::PERMISSION_EDIT => 4,
						Acl::PERMISSION_MANAGE => 8,
						Acl::PERMISSION_SHARE => 8,
					];
					$levelOk = ($level >= ($levelRequired[$permission] ?? 1));
					return $levelOk && $acl->getPermission($permission);
				} catch (\Exception $e) {
					$this->logger->info('Member not found in circle that was accessed. This should not happen.');
				}
			}
		}

		$hasGroupPermission = false;
		foreach ($acls as $acl) {
			if (!$hasGroupPermission && $acl->getType() === Acl::PERMISSION_TYPE_GROUP && $this->groupManager->isInGroup($userId, $acl->getParticipant())) {
				$hasGroupPermission = $acl->getPermission($permission);
			}
		}
		return $hasGroupPermission;
	}

	/**
	 * Find a list of all users (including the ones from groups)
	 *
	 * @param string $object_type
	 * @param int $object_id
	 * @param bool $refresh
	 * @return array
	 */
	public function findUsers($object_type, $object_id, $refresh = false) {
		if (array_key_exists((string) $object_id, $this->users) && !$refresh) {
			return $this->users[(string) $object_id];
		}
		try {
			if ($object_type === 'cc_Case') {
				$case = $this->caseMapper->find($object_id);
				$owner = $case->getOwner();
				$id = $case->getId();
			} else {
				return [];
			}
		} catch (DoesNotExistException $e) {
			return [];
		} catch (MultipleObjectsReturnedException $e) {
			return [];
		}

		$users = [];
		$ownerUser = $this->userManager->get($owner);
		if ($ownerUser === null) {
			$this->logger->info('No owner found for cc_Case ' . $id);
		} else {
			$users[$ownerUser->getUID()] = new \OCA\Charity\Db\User($ownerUser);
		}

		$acls = $this->aclMapper->findAll($object_type, $object_id);
		foreach ($acls as $acl) {
			if ($acl->getType() === Acl::PERMISSION_TYPE_USER) {
				$user = $this->userManager->get($acl->getParticipant());
				if ($user === null) {
					$this->logger->info('No user found for acl rule ' . $acl->getId());
					continue;
				}
				$users[$user->getUID()] = new \OCA\Charity\Db\User($user);
			}
			if ($acl->getType() === Acl::PERMISSION_TYPE_GROUP) {
				$group = $this->groupManager->get($acl->getParticipant());
				if ($group === null) {
					$this->logger->info('No group found for acl rule ' . $acl->getId());
					continue;
				}
				foreach ($group->getUsers() as $user) {
					$users[$user->getUID()] = new \OCA\Charity\Db\User($user);
				}
			}

			if ($this->circlesEnabled && $acl->getType() === Acl::PERMISSION_TYPE_CIRCLE) {
				try {
					$circle = \OCA\Circles\Api\v1\Circles::detailsCircle($acl->getParticipant(), true);
					if ($circle === null) {
						$this->logger->info('No circle found for acl rule ' . $acl->getId());
						continue;
					}

					foreach ($circle->getMembers() as $member) {
						$user = $this->userManager->get($member->getUserId());
						if ($user === null) {
							$this->logger->info('No user found for circle member ' . $member->getUserId());
						} else {
							$users[$member->getUserId()] = new \OCA\Charity\Db\User($user);
						}
					}
				} catch (\Exception $e) {
					$this->logger->info('Member not found in circle that was accessed. This should not happen.');
				}
			}
		}
		$this->users[(string) $object_id] = $users;
		return $this->users[(string) $object_id];
	}

	public function canCreate() {
		$groups = $this->getGroupLimitList();

		if (count($groups) === 0) {
			return true;
		}
		foreach ($groups as $group) {
			if ($this->groupManager->isInGroup($this->userId, $group)) {
				return true;
			}
		}
		return false;
	}

	private function getGroupLimitList() {
		$value = $this->config->getAppValue('charity', 'groupLimit', '');
		$groups = explode(',', $value);
		if ($value === '') {
			return [];
		}
		return $groups;
	}
}
