<?php
namespace OCA\Charity\Db;

class cc_Payment extends RelationalEntity {
    protected $caseId;
    protected $paymentDate;
    protected $paymentReceipt;
    protected $paidBy;
    protected $paymentType;
    protected $paymentAmount;
    protected $paymentReference;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('caseId', 'integer');
        $this->addType('paymentDate', 'datetime');
        $this->addType('paymentReceipt', 'string');
        $this->addType('paidBy', 'string');
        $this->addType('paymentType', 'string');
        $this->addType('paymentAmount', 'float');
        $this->addType('paymentReference', 'string');
    }

    public function jsonSerialize() {
        $json = parent::jsonSerialize();
        if ($this->paymentDate instanceof \DateTime) {
            $json['paymentDate'] = $this->paymentDate->format('Y-m-d');
        }
        return $json;
    }
}
