<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_PaymentMapper extends CharityMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'cc_payment', cc_Payment::class);
    }

    public function find($id): ?cc_Payment {
        $sql = 'SELECT * FROM `*PREFIX*cc_payment` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM `*PREFIX*cc_payment`';
        return $this->findEntitiesString($sql, []);
    }

    public function findByCase($caseId) {
        $sql = 'SELECT * FROM `*PREFIX*cc_payment` WHERE `case_id` = ?';
        return $this->findEntitiesString($sql, [$caseId]);
    }
}
