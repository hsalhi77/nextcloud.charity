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

    public function findAll($param = []) {
        $sql = 'SELECT * FROM `*PREFIX*cc_update` WHERE 1=1';
        $bindings = [];
        $columnMap = [
            'caseId' => 'case_id',
            'updateDate' => 'update_date',
            'updateTypeId' => 'update_type_id',
            'updateBy' => 'update_by',
        ];
        foreach ($param as $key => $val) {
            if ($key === '' || $key[0] === '_' || $val === '') {
                continue;
            }
            $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if ($cleanKey === '') {
                continue;
            }
            $column = $columnMap[$cleanKey] ?? $cleanKey;
            $sql .= ' AND `' . $column . '` = ?';
            $bindings[] = $val;
        }
        return $this->findEntitiesString($sql, $bindings);
    }

    public function findByCase($caseId) {
        $sql = 'SELECT * FROM `*PREFIX*cc_update` WHERE `case_id` = ?';
        return $this->findEntitiesString($sql, [$caseId]);
    }
}
