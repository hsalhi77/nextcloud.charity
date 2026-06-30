<?php
namespace OCA\Charity\Db;

class cc_Update extends RelationalEntity {
    protected $caseId;
    protected $updateDate;
    protected $updateTypeId;
    protected $updateBy;
    protected $description;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('caseId', 'integer');
        $this->addType('updateDate', 'datetime');
        $this->addType('updateTypeId', 'integer');
        $this->addType('updateBy', 'string');
        $this->addType('description', 'string');
    }

    public function jsonSerialize() {
        $json = parent::jsonSerialize();
        if ($this->updateDate instanceof \DateTime) {
            $json['updateDate'] = $this->updateDate->format('Y-m-d');
        }
        return $json;
    }
}
