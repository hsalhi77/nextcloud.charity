<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_attachmentMapper extends CharityMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'cc_attachment', cc_attachment::class);
	}

	public function findAll(int $object_id, string $object_type) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('isactive', $qb->createNamedParameter(1)))
			->andWhere($qb->expr()->eq('object_type', $qb->createNamedParameter($object_type)));

		if ($object_id !== 0) {
			$qb->andWhere($qb->expr()->eq('object_id', $qb->createNamedParameter($object_id)));
		}

		return $this->findEntities($qb);
	}

	public function findByObject($object_type, $object_id) {
		return $this->findAll((int)$object_id, (string)$object_type);
	}
}
