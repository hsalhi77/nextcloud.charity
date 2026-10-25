<?php
namespace OCA\Charity\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class cc_AssetMapper extends CharityMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'cc_assets', cc_Asset::class);
	}

	public function find($id): ?cc_Asset {
		$sql = 'SELECT * FROM `*PREFIX*cc_assets` WHERE `id` = ?';
		return $this->findEntityString($sql, [$id]);
	}

	public function findAll($param = []) {
		$sql = 'SELECT * FROM `*PREFIX*cc_assets` WHERE 1=1';
		$bindings = [];
		$columnMap = [
			'refPaymentId' => 'ref_payment_id',
			'caseId' => 'case_id',
			'assetType' => 'asset_type',
		];
		foreach ($param as $key => $val) {
			if ($key === '' || $key[0] === '_' || $val === '' || $val === null) {
				continue;
			}
			$cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
			if ($cleanKey === '') {
				continue;
			}
			if ($cleanKey === 'search') {
				$sql .= ' AND (`serial` LIKE ? OR `model` LIKE ?)';
				$bindings[] = '%' . $val . '%';
				$bindings[] = '%' . $val . '%';
				continue;
			}
			$column = $columnMap[$cleanKey] ?? $cleanKey;
			$sql .= ' AND `' . $column . '` = ?';
			$bindings[] = $val;
		}
		$sql .= ' ORDER BY `id` DESC';
		return $this->findEntitiesString($sql, $bindings);
	}

	public function findByPayment(int $paymentId): array {
		$sql = 'SELECT * FROM `*PREFIX*cc_assets` WHERE `ref_payment_id` = ?';
		return $this->findEntitiesString($sql, [$paymentId]);
	}

	public function findByCase(int $caseId): array {
		$sql = 'SELECT * FROM `*PREFIX*cc_assets` WHERE `case_id` = ?';
		return $this->findEntitiesString($sql, [$caseId]);
	}

	public function deleteByPayment(int $paymentId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('cc_assets')
			->where($qb->expr()->eq('ref_payment_id', $qb->createNamedParameter($paymentId, IQueryBuilder::PARAM_INT)));
		$qb->executeStatement();
	}
}
