<?php
namespace OCA\Charity\Service;

use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

class DashboardService {
	private IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	public function getStats(): array {
		return [
			'totalCases' => $this->getTotalCases(),
			'casesByType' => $this->getCasesByType(),
			'totalReceipts' => $this->getPaymentTotal('Receipt'),
			'totalPayments' => $this->getPaymentTotal('Payment'),
			'totalExpensePayments' => $this->getPaymentTotal('Expense Payment'),
			'cityStats' => $this->getCityStats(),
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
		$qb->select($qb->func()->sum('payment_amount', 'total'))
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
}
