<?php
declare(strict_types=1);

namespace OCA\Charity\Service;

use OCA\Circles\CirclesManager;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\MembershipService;
use OCA\Charity\Exceptions\BadRequestException;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class TeamService {
	private CirclesManager $circlesManager;
	private MembershipService $membershipService;
	private IUserManager $userManager;
	private IGroupManager $groupManager;
	private ?string $userId;
	private bool $circlesEnabled;
	private LoggerInterface $logger;

	public function __construct(
		CirclesManager $circlesManager,
		MembershipService $membershipService,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IAppManager $appManager,
		LoggerInterface $logger,
		$userId
	) {
		$this->circlesManager = $circlesManager;
		$this->membershipService = $membershipService;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userId = $userId;
		$this->circlesEnabled = $appManager->isEnabledForUser('circles');
		$this->logger = $logger;
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
			$this->logger->error('Charity: Failed to create case circle: ' . $e->getMessage(), ['app' => 'charity']);
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
			$this->logger->error('Charity: Failed to add member: ' . $e->getMessage(), ['app' => 'charity']);
			return false;
		}
	}

	/**
	 * Remove a member from a circle using the MemberRequest::delete() workaround.
	 */
	public function deleteMember(string $circleId, string $memberId): bool {
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

			$memberRequest = Server::get(\OCA\Circles\Db\MemberRequest::class);
			$member = $memberRequest->getMemberById($memberId);

			if ($member === null || $member->getLevel() === Member::LEVEL_OWNER) {
				return false;
			}

			try {
				$memberRequest->delete($member);
			} catch (\Throwable $e) {
				$this->logger->error('Charity: delete member failed: ' . $e->getMessage(), ['app' => 'charity']);
				return false;
			}

			try {
				$federatedUser = $this->circlesManager->getFederatedUser($member->getUserId(), Member::TYPE_USER);
				$this->membershipService->deleteFederatedUser($federatedUser);
			} catch (\Throwable $e) {
				$this->logger->error('Charity: deleteFederatedUser failed: ' . $e->getMessage(), ['app' => 'charity']);
			}

			return true;
		} catch (\Throwable $e) {
			$this->logger->error('Charity: Failed to delete member: ' . $e->getMessage(), ['app' => 'charity']);
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
			$this->logger->error('Charity: Failed to get circle members: ' . $e->getMessage(), ['app' => 'charity']);
		}

		return $result;
	}

	/**
	 * Get users in a specific group.
	 */
	public function getUsersByGroup(string $groupName): array {
		$group = $this->groupManager->get($groupName);
		if (!$group) {
			// Fallback: look up by display name (needed when group ID differs from group name)
			$lower = strtolower($groupName);
			foreach ($this->groupManager->search($groupName) as $g) {
				if (strtolower($g->getDisplayName()) === $lower) {
					$group = $g;
					break;
				}
			}
		}
		if (!$group) return [];
		$result = [];
		foreach ($group->getUsers() as $user) {
			$result[] = [
				'uid' => $user->getUID(),
				'displayName' => $user->getDisplayName(),
			];
		}
		return $result;
	}

	/**
	 * Get the current user's group IDs.
	 */
	public function getUserGroups(): array {
		if (!$this->userId) return [];
		$user = $this->userManager->get($this->userId);
		if (!$user) return [];
		return $this->groupManager->getUserGroupIds($user);
	}

	/**
	 * Check if current user is in a specific group (case-insensitive).
	 */
	public function userInGroup(string $groupName): bool {
		$lower = strtolower($groupName);
		foreach ($this->getUserGroups() as $g) {
			if (strtolower($g) === $lower) return true;
		}
		return false;
	}

	/**
	 * Check if current user has admin-level access (Charity Admin or Admin).
	 */
	public function isAdmin(): bool {
		return $this->userInGroup('Admin') || $this->userInGroup('Charity Admin');
	}

	/**
	 * Get users in a group, filtered by role.
	 * Charity users only see themselves.
	 */
	public function getUsersByGroupFiltered(string $groupName): array {
		if ($this->isAdmin()) {
			return $this->getUsersByGroup($groupName);
		}
		if (!$this->userId) return [];
		$user = $this->userManager->get($this->userId);
		if (!$user) return [];
		return [
			[
				'uid' => $user->getUID(),
				'displayName' => $user->getDisplayName(),
			],
		];
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
