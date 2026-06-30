<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_UpdateTypeMapper extends CharityMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'cc_updateType', cc_UpdateType::class);
    }

    public function find($id): ?cc_UpdateType {
        $sql = 'SELECT * FROM `*PREFIX*cc_updateType` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM `*PREFIX*cc_updateType`';
        return $this->findEntitiesString($sql, []);
    }
}
