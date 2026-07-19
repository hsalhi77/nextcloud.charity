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

    public function findAll($param = []) {
        $sql = 'SELECT * FROM `*PREFIX*cc_payment` WHERE 1=1';
        $bindings = [];
        $columnMap = [
            'caseId' => 'case_id',
            'paymentDate' => 'payment_date',
            'paymentType' => 'payment_type',
            'paymentAmount' => 'payment_amount',
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
        $sql = 'SELECT * FROM `*PREFIX*cc_payment` WHERE `case_id` = ?';
        return $this->findEntitiesString($sql, [$caseId]);
    }
}
