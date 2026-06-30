<?php
namespace OCA\Charity\Db;

class cc_Payment extends RelationalEntity {
    protected $caseId;
    protected $paymentDate;
    protected $paymentReceipt;
    protected $paidBy;
    protected $paymentType;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('caseId', 'integer');
        $this->addType('paymentDate', 'datetime');
        $this->addType('paymentReceipt', 'string');
        $this->addType('paidBy', 'string');
        $this->addType('paymentType', 'string');
    }

    public function jsonSerialize() {
        $json = parent::jsonSerialize();
        if ($this->paymentDate instanceof \DateTime) {
            $json['paymentDate'] = $this->paymentDate->format('Y-m-d');
        }
        return $json;
    }
}
