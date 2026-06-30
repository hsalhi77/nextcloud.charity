<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_CityMapper extends CharityMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'cc_city', cc_City::class);
    }

    public function find($id): ?cc_City {
        $sql = 'SELECT * FROM `*PREFIX*cc_city` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM `*PREFIX*cc_city`';
        return $this->findEntitiesString($sql, []);
    }
}
