<?php
namespace OCA\Charity\Db;

class cc_Transfer extends RelationalEntity {
    protected $transferDate;
    protected $ref;
    protected $description;
    protected $amount;
    protected $paidFrom;
    protected $paidTo;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('transferDate', 'datetime');
        $this->addType('ref', 'string');
        $this->addType('description', 'string');
        $this->addType('amount', 'float');
        $this->addType('paidFrom', 'string');
        $this->addType('paidTo', 'string');
    }

    public function jsonSerialize() {
        $json = parent::jsonSerialize();
        if ($this->transferDate instanceof \DateTime) {
            $json['transferDate'] = $this->transferDate->format('Y-m-d');
        }
        return $json;
    }
}
