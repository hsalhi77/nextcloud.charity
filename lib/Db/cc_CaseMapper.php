<?php
namespace OCA\Charity\Db;

use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IGroupManager;

class cc_CaseMapper extends CharityMapper {
    private $userManager;
    private $groupManager;

    public function __construct(IDBConnection $db, IUserManager $userManager, IGroupManager $groupManager) {
        parent::__construct($db, 'cc_case', cc_Case::class);
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
    }

    public function find($id): ?cc_Case {
        $sql = 'SELECT * FROM `*PREFIX*cc_case` WHERE `id` = ?';
        return $this->findEntityString($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM `*PREFIX*cc_case` WHERE isactive = 1';
        return $this->findEntitiesString($sql, []);
    }

    public function findAllByUser($userId, $param = []) {
        $sql = 'SELECT *, 0 as shared FROM `*PREFIX*cc_case` WHERE owner = ? AND isactive = 1';
        $bindings = [$userId];
        foreach ($param as $key => $val) {
            if ($key === '' || $key[0] === '_' || $val === '') {
                continue;
            }
            $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if ($cleanKey === '') {
                continue;
            }
            $sql .= ' AND `' . $cleanKey . '` = ?';
            $bindings[] = $val;
        }
        return $this->findEntitiesString($sql, $bindings);
    }

    public function mapOwner(cc_Case &$item) {
        $userManager = $this->userManager;
        $item->resolveRelation('owner', function ($owner) use (&$userManager) {
            $user = $userManager->get($owner);
            if ($user !== null) {
                return new \OCA\Charity\Db\User($user);
            }
            return null;
        });
    }
}
