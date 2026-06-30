<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_CaseTypeMapper extends CharityMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'cc_caseType', cc_CaseType::class);
    }

    public function find($id): ?cc_CaseType {
        $sql = 'SELECT * FROM `*PREFIX*cc_caseType` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM `*PREFIX*cc_caseType`';
        return $this->findEntitiesString($sql, []);
    }
}
