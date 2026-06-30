<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_UpdateTypeMapper extends CharityMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'cc_update_type', cc_UpdateType::class);
    }

    public function find($id): ?cc_UpdateType {
        $sql = 'SELECT * FROM `*PREFIX*cc_update_type` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM `*PREFIX*cc_update_type`';

        return $this->findEntitiesString($sql, []);
    }
}
