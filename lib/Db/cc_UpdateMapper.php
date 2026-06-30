<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_UpdateMapper extends CharityMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'cc_update', cc_Update::class);
    }

    public function find($id): ?cc_Update {
        $sql = 'SELECT * FROM `*PREFIX*cc_update` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM `*PREFIX*cc_update`';
        return $this->findEntitiesString($sql, []);
    }

    public function findByCase($caseId) {
        $sql = 'SELECT * FROM `*PREFIX*cc_update` WHERE `case_id` = ?';
        return $this->findEntitiesString($sql, [$caseId]);
    }
}
