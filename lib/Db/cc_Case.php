<?php
namespace OCA\Charity\Db;

class cc_Case extends RelationalEntity {
    protected $shared;
    protected $dateAdded;
    protected $referredBy;
    protected $firstName;
    protected $lastName;
    protected $idNumber;
    protected $cityId;
    protected $town;
    protected $location;
    protected $dob;
    protected $dependants;
    protected $caseTypeId;
    protected $description;
    protected $recommendation;
    protected $owner;
    protected $circleId;
    protected $created;
    protected $updated;
    protected $isactive;
    protected $acl = [];
    protected $permissions = [];

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('shared', 'integer');
        $this->addType('dateAdded', 'datetime');
        $this->addType('cityId', 'integer');
        $this->addType('caseTypeId', 'integer');
        $this->addType('dob', 'datetime');
        $this->addType('dependants', 'integer');
        $this->addType('circleId', 'string');
        $this->addType('created', 'datetime');
        $this->addType('updated', 'datetime');
        $this->addType('isactive', 'boolean');
        $this->addRelation('acl');
        $this->addRelation('permissions');
        $this->addResolvable('owner');
    }

    public function jsonSerialize() {
        $json = parent::jsonSerialize();
        $json['acl'] = $this->acl ?? [];
        if ($this->dob instanceof \DateTime) {
            $json['dob'] = $this->dob->format('Y-m-d');
        }
        if ($this->dateAdded instanceof \DateTime) {
            $json['dateAdded'] = $this->dateAdded->format('Y-m-d');
        }
        return $json;
    }

    public function setAcl($acl) {
        foreach ($acl as $a) {
            $this->acl[] = $a;
        }
    }

    public function getAcl(): ?array {
        return $this->acl;
    }
}
