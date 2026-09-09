<?php
namespace OCA\Charity\Service;

use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IGroupManager;
use OCP\IUserManager;

class DashboardService {
	private IDBConnection $db;
	private IGroupManager $groupManager;
	private IUserManager $userManager;

	public function __construct(IDBConnection $db, IGroupManager $groupManager, IUserManager $userManager) {
		$this->db = $db;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
	}

	public function getStats(): array {
		return [
			'totalCases' => $this->getTotalCases(),
			'casesByType' => $this->getCasesByType(),
			'totalReceipts' => $this->getPaymentTotal('Receipt'),
			'totalPayments' => $this->getPaymentTotal('Payment'),
			'totalExpensePayments' => $this->getPaymentTotal('Expense Payment'),
			'totalTransferPayments' => $this->getPaymentTotal('Transfer Payment'),
			'totalTransferReceipts' => $this->getPaymentTotal('Transfer Receipt'),
			'cityStats' => $this->getCityStats(),
			'activityByField' => $this->getActivityByField(),
			'paymentsByAgentAndCaseType' => $this->getPaymentsByAgentAndCaseType(),
		];
	}

	private function getTotalCases(): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count('*', 'total'))
			->from('cc_case')
			->where($qb->expr()->eq('isactive', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		return (int)($row['total'] ?? 0);
	}

	private function getCasesByType(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('ct.id', 'ct.title')
			->selectAlias($qb->func()->count('c.id'), 'count')
			->from('cc_case_type', 'ct')
			->leftJoin('ct', 'cc_case', 'c', $qb->expr()->andX(
				$qb->expr()->eq('c.case_type_id', 'ct.id'),
				$qb->expr()->eq('c.isactive', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
			))
			->groupBy('ct.id', 'ct.title')
			->orderBy('ct.title', 'ASC');
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		return array_map(function ($row) {
			return [
				'id' => (int)$row['id'],
				'title' => $row['title'],
				'count' => (int)$row['count'],
			];
		}, $rows);
	}

	private function getPaymentTotal(string $type): float {
		$qb = $this->db->getQueryBuilder();
		$qb->selectAlias($qb->func()->sum('payment_amount'), 'total')
			->from('cc_payment')
			->where($qb->expr()->eq('payment_type', $qb->createNamedParameter($type)));
		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		return (float)($row['total'] ?? 0);
	}

	private function getCityStats(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('city.id', 'city.title')
			->selectAlias($qb->createFunction('COUNT(DISTINCT `case`.`id`)'), 'case_count')
			->selectAlias($qb->createFunction('COALESCE(SUM(`payment`.`payment_amount`), 0)'), 'paid_amount')
			->from('cc_city', 'city')
			->leftJoin('city', 'cc_case', 'case', $qb->expr()->andX(
				$qb->expr()->eq('case.city_id', 'city.id'),
				$qb->expr()->eq('case.isactive', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
			))
			->leftJoin('case', 'cc_payment', 'payment', $qb->expr()->andX(
				$qb->expr()->eq('payment.case_id', 'case.id'),
				$qb->expr()->eq('payment.payment_type', $qb->createNamedParameter('Payment'))
			))
			->groupBy('city.id', 'city.title')
			->orderBy('case_count', 'DESC')
			->addOrderBy('city.title', 'ASC');
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		return array_map(function ($row) {
			return [
				'id' => (int)$row['id'],
				'name' => $row['title'],
				'caseCount' => (int)$row['case_count'],
				'paidAmount' => (float)$row['paid_amount'],
			];
		}, $rows);
	}

	private function getPaymentsByAgentAndCaseType(): array {
		[$startDate, $endDate] = $this->getActivityWindow();
		$qb = $this->db->getQueryBuilder();
		$qb->select('payment.payment_date', 'payment.paid_by', 'case_type.title')
			->selectAlias($qb->func()->sum('payment.payment_amount'), 'total_amount')
			->from('cc_payment', 'payment')
			->innerJoin('payment', 'cc_case', 'case', $qb->expr()->eq('payment.case_id', 'case.id'))
			->innerJoin('case', 'cc_case_type', 'case_type', $qb->expr()->eq('case.case_type_id', 'case_type.id'))
			->where($qb->expr()->eq('payment.payment_type', $qb->createNamedParameter('Payment')))
			->andWhere($qb->expr()->gte('payment.payment_date', $qb->createNamedParameter($startDate)))
			->andWhere($qb->expr()->lte('payment.payment_date', $qb->createNamedParameter($endDate)))
			->groupBy('payment.payment_date', 'payment.paid_by', 'case_type.title')
			->orderBy('payment.payment_date', 'DESC')
			->addOrderBy('case_type.title', 'ASC')
			->addOrderBy('payment.paid_by', 'ASC');
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		$data = [];
		foreach ($rows as $row) {
			$ym = (new \DateTime($row['payment_date']))->format('Y-m');
			$user = $this->userManager->get($row['paid_by']);
			$data[] = [
				'month' => $ym,
				'monthLabel' => (new \DateTime($row['payment_date']))->format('M-y'),
				'agentUid' => $row['paid_by'],
				'agentName' => $user ? $user->getDisplayName() : $row['paid_by'],
				'caseType' => $row['title'],
				'totalAmount' => (float)$row['total_amount'],
			];
		}
		return $data;
	}

	private function getActivityWindow(): array {
		$start = (new \DateTime('first day of this month'))->modify('-5 months')->format('Y-m-d');
		$end = (new \DateTime('last day of this month'))->format('Y-m-d');
		return [$start, $end];
	}

	private function getCharityFieldUsers(): array {
		$groupName = 'Charity Field';
		$group = null;
		foreach ($this->groupManager->search($groupName) as $g) {
			if (strtolower($g->getDisplayName()) === strtolower($groupName)) {
				$group = $g;
				break;
			}
		}
		if (!$group) {
			$group = $this->groupManager->get($groupName);
		}
		return $group ? $group->getUsers() : [];
	}

	private function getMonthLabels(): array {
		$labels = [];
		$date = new \DateTime('first day of this month');
		for ($i = 0; $i < 6; $i++) {
			$ym = $date->format('Y-m');
			$labels[$ym] = $date->format('M-y');
			$date->modify('-1 month');
		}
		return array_reverse($labels, true);
	}

	private function aggregateByMonthAndUser(string $table, string $userColumn, string $dateColumn, array $userIds, string $startDate, string $endDate): array {
		$counts = [];
		$qb = $this->db->getQueryBuilder();
		$qb->select($userColumn, $dateColumn)
			->from($table)
			->where($qb->expr()->gte($dateColumn, $qb->createNamedParameter($startDate)))
			->andWhere($qb->expr()->lte($dateColumn, $qb->createNamedParameter($endDate)))
			->andWhere($qb->expr()->in($userColumn, $qb->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)));
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$ym = (new \DateTime($row[$dateColumn]))->format('Y-m');
			$uid = $row[$userColumn];
			if (!isset($counts[$ym])) {
				$counts[$ym] = [];
			}
			if (!isset($counts[$ym][$uid])) {
				$counts[$ym][$uid] = 0;
			}
			$counts[$ym][$uid]++;
		}
		$result->closeCursor();
		return $counts;
	}

	private function getActivityByField(): array {
		[$startDate, $endDate] = $this->getActivityWindow();
		$users = $this->getCharityFieldUsers();
		if (empty($users)) {
			return [];
		}
		$userIds = array_keys($users);
		$months = $this->getMonthLabels();

		// Initialise the matrix: every month has a slot for every user.
		$matrix = [];
		foreach ($months as $ym => $label) {
			$matrix[$ym] = ['month' => $label, 'users' => []];
			foreach ($users as $uid => $user) {
				$matrix[$ym]['users'][$uid] = [
					'uid' => $uid,
					'displayName' => $user->getDisplayName(),
					'cases' => 0,
					'payments' => 0,
					'updates' => 0,
				];
			}
		}

		foreach ($this->aggregateByMonthAndUser('cc_case', 'referred_by', 'date_added', $userIds, $startDate, $endDate) as $ym => $counts) {
			foreach ($counts as $uid => $count) {
				if (isset($matrix[$ym]['users'][$uid])) {
					$matrix[$ym]['users'][$uid]['cases'] = $count;
				}
			}
		}
		foreach ($this->aggregateByMonthAndUser('cc_payment', 'paid_by', 'payment_date', $userIds, $startDate, $endDate) as $ym => $counts) {
			foreach ($counts as $uid => $count) {
				if (isset($matrix[$ym]['users'][$uid])) {
					$matrix[$ym]['users'][$uid]['payments'] = $count;
				}
			}
		}
		foreach ($this->aggregateByMonthAndUser('cc_update', 'update_by', 'update_date', $userIds, $startDate, $endDate) as $ym => $counts) {
			foreach ($counts as $uid => $count) {
				if (isset($matrix[$ym]['users'][$uid])) {
					$matrix[$ym]['users'][$uid]['updates'] = $count;
				}
			}
		}

		// Determine which users had any activity in the window.
		$totals = [];
		foreach ($userIds as $uid) {
			$totals[$uid] = 0;
		}
		foreach ($matrix as $monthData) {
			foreach ($monthData['users'] as $uid => $u) {
				$totals[$uid] += $u['cases'] + $u['payments'] + $u['updates'];
			}
		}
		$activeUserIds = array_filter($userIds, static function ($uid) use ($totals) {
			return $totals[$uid] > 0;
		});
		usort($activeUserIds, static function ($a, $b) use ($totals, $users) {
			if ($totals[$b] !== $totals[$a]) {
				return $totals[$b] <=> $totals[$a];
			}
			return strcasecmp($users[$a]->getDisplayName(), $users[$b]->getDisplayName());
		});

		if (empty($activeUserIds)) {
			return [];
		}

		// Drop months with no activity at all.
		$matrix = array_filter($matrix, static function ($monthData) use ($activeUserIds) {
			foreach ($activeUserIds as $uid) {
				$u = $monthData['users'][$uid];
				if (($u['cases'] + $u['payments'] + $u['updates']) > 0) {
					return true;
				}
			}
			return false;
		});

		if (empty($matrix)) {
			return [];
		}

		// Build the final array, keeping only active users in each month.
		$result = [];
		foreach ($matrix as $ym => $monthData) {
			$monthUsers = [];
			foreach ($activeUserIds as $uid) {
				$monthUsers[] = $monthData['users'][$uid];
			}
			$result[] = [
				'month' => $monthData['month'],
				'users' => $monthUsers,
			];
		}
		return $result;
	}
}
