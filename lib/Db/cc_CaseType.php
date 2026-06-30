<?php
namespace OCA\Charity\Db;

class cc_CaseType extends RelationalEntity {
    protected $title;
    protected $isactive;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('title', 'string');
        $this->addType('isactive', 'boolean');
    }
}
