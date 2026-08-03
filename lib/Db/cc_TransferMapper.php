<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;

class cc_TransferMapper extends CharityMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'cc_transfers', cc_Transfer::class);
    }

    public function find($id): ?cc_Transfer {
        $sql = 'SELECT * FROM `*PREFIX*cc_transfers` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll($param = []) {
        $sql = 'SELECT * FROM `*PREFIX*cc_transfers` WHERE 1=1';
        $bindings = [];
        $columnMap = [
            'transferDate' => 'transfer_date',
            'paidFrom' => 'paid_from',
            'paidTo' => 'paid_to',
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
}
