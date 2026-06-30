<?php
declare(strict_types=1);

namespace OCA\Charity\Service;

use OCA\Circles\CirclesManager;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Charity\Exceptions\BadRequestException;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;

class TeamService {
	private CirclesManager $circlesManager;
	private IUserManager $userManager;
	private IGroupManager $groupManager;
	private ?string $userId;
	private bool $circlesEnabled;

	public function __construct(
		CirclesManager $circlesManager,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IAppManager $appManager,
		$userId
	) {
		$this->circlesManager = $circlesManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userId = $userId;
		$this->circlesEnabled = $appManager->isEnabledForUser('circles');
	}

	/**
	 * Create a new circle to be used as a case team.
	 */
	public function createCaseCircle(string $name): ?string {
		if (!$this->circlesEnabled) {
			return null;
		}

		try {
			$owner = $this->circlesManager->getLocalFederatedUser($this->userId);
			$this->circlesManager->startSession($owner);
			return $this->circlesManager->createCircle($name, $owner)->getSingleId();
		} catch (\Throwable $e) {
			\OC::$server->getLogger()->error('Charity: Failed to create case circle: ' . $e->getMessage(), ['app' => 'charity']);
			return null;
		}
	}

	/**
	 * Add a user to a circle.
	 */
	public function addMember(string $circleId, string $userId, int $level = Member::LEVEL_MEMBER): bool {
		if (!$this->circlesEnabled) {
			return false;
		}

		try {
			$circle = $this->getCircle($circleId);
			if ($circle === null) {
				return false;
			}

			$owner = $this->circlesManager->getLocalFederatedUser($circle->getOwner()->getUserId());
			$this->circlesManager->startSession($owner);

			$federatedUser = $this->circlesManager->getFederatedUser($userId, Member::TYPE_USER);
			$member = $this->circlesManager->addMember($circle->getSingleId(), $federatedUser);

			if ($level !== Member::LEVEL_MEMBER) {
				$this->circlesManager->levelMember($member->getId(), $level);
			}

			return true;
		} catch (\Throwable $e) {
			\OC::$server->getLogger()->error('Charity: Failed to add member: ' . $e->getMessage(), ['app' => 'charity']);
			return false;
		}
	}

	/**
	 * Remove a user from a circle using the MemberRequest::delete() workaround.
	 */
	public function deleteMember(string $circleId, string $userId): bool {
		if (!$this->circlesEnabled) {
			return false;
		}

		$circle = $this->getCircle($circleId);
		if ($circle === null) {
			return false;
		}

		try {
			$owner = $this->circlesManager->getLocalFederatedUser($circle->getOwner()->getUserId());
			$this->circlesManager->startSession($owner);
			$members = $circle->getMembers();
			foreach ($members as $member) {
				if ($member->getUserId() === $userId && $member->getLevel() !== Member::LEVEL_OWNER) {
					try {
						$memberRequest = Server::get(\OCA\Circles\Db\MemberRequest::class);
						$memberRequest->delete($member);
					} catch (\Throwable $e) {
						\OC::$server->getLogger()->error('Charity: delete member failed: ' . $e->getMessage(), ['app' => 'charity']);
					}
					return true;
				}
			}
		} catch (\Throwable $e) {
			\OC::$server->getLogger()->error('Charity: Failed to delete member: ' . $e->getMessage(), ['app' => 'charity']);
		}

		return false;
	}

	/**
	 * List members of a circle.
	 */
	public function getCircleMembers(string $circleId): array {
		$result = [];
		if (!$this->circlesEnabled) {
			return $result;
		}

		try {
			$circle = $this->getCircle($circleId);
			if ($circle !== null) {
				foreach ($circle->getMembers() as $member) {
					$result[] = [
						'userId' => $member->getUserId(),
						'displayName' => $member->getDisplayName(),
						'level' => $member->getLevel(),
					];
				}
			}
		} catch (\Throwable $e) {
			\OC::$server->getLogger()->error('Charity: Failed to get circle members: ' . $e->getMessage(), ['app' => 'charity']);
		}

		return $result;
	}

	/**
	 * Search Nextcloud users.
	 */
	public function searchUsers(?string $search): array {
		$users = $this->userManager->search($search === null ? '' : $search);
		$result = [];
		foreach ($users as $user) {
			$result[] = [
				'uid' => $user->getUID(),
				'displayName' => $user->getDisplayName(),
				'groups' => $this->groupManager->getUserGroupIds($user),
				'enabled' => $user->isEnabled(),
			];
		}
		return $result;
	}

	/**
	 * Toggle user enabled state.
	 */
	public function toggleUserEnabled(string $uid): array {
		$user = $this->userManager->get($uid);
		if ($user === null) {
			throw new BadRequestException('User not found');
		}
		$enabled = !$user->isEnabled();
		$user->setEnabled($enabled);
		return ['enabled' => $enabled];
	}

	/**
	 * Find a member in a circle by user id.
	 */
	public function getMemberByUserId(string $circleId, string $userId): ?Member {
		if (!$this->circlesEnabled) {
			return null;
		}

		try {
			$circle = $this->getCircle($circleId);
			if ($circle === null) {
				return null;
			}

			foreach ($circle->getMembers() as $member) {
				if ($member->getUserId() === $userId) {
					return $member;
				}
			}
		} catch (\Throwable $e) {
		}

		return null;
	}

	/**
	 * Fetch a circle using a super session so that members are visible.
	 */
	private function getCircle(string $circleId): ?Circle {
		if (!$this->circlesEnabled) {
			return null;
		}

		try {
			$circlesManager = Server::get(CirclesManager::class);
			$circlesManager->startSuperSession();
			return $circlesManager->getCircle($circleId);
		} catch (\Throwable $e) {
		}

		return null;
	}
}
