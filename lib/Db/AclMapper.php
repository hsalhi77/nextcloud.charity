<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Charity\Db;

use OCP\IDBConnection;

class AclMapper extends CharityMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'cc_acl', Acl::class);
	}

	public function find($id): ?Acl {
		$sql = 'SELECT id, object_id, object_type, type, participant, permission_edit, permission_share, permission_manage, shareid, parentid ' .
			'FROM `*PREFIX*cc_acl` WHERE `id` = ?';
		return $this->findEntityString($sql, [$id]);
	}

	public function findAll($object_type, $object_id) {
		$sql = 'SELECT id, object_id, object_type, type, participant, permission_edit, permission_share, permission_manage, shareid, parentid ' .
			'FROM `*PREFIX*cc_acl` WHERE `object_id` = ? AND `object_type` = ?';
		return $this->findEntitiesString($sql, [$object_id, $object_type]);
	}

	public function findByParticipant($type, $object_type, $participant) {
		$sql = 'SELECT id, object_id, object_type, type, participant, permission_edit, permission_share, permission_manage ' .
			'FROM `*PREFIX*cc_acl` WHERE `type` = ? AND `object_type` = ? AND `participant` = ?';
		return $this->findEntitiesString($sql, [$type, $object_type, $participant]);
	}

	public function findByParticipantobject($type, $participant, $object_type, $object_id) {
		$sql = 'SELECT id, object_id, object_type, type, participant, permission_edit, permission_share, permission_manage ' .
			'FROM `*PREFIX*cc_acl` WHERE `type` = ? AND `object_id` = ? AND `object_type` = ? AND `participant` = ?';
		return $this->findEntitiesString($sql, [$type, $object_id, $object_type, $participant]);
	}

	public function findByParent(int $parentid) {
		$sql = 'SELECT id, object_id, object_type, type, participant, permission_edit, permission_share, permission_manage ' .
			'FROM `*PREFIX*cc_acl` WHERE `isactive` = 1 AND `parentid` = ?';
		return $this->findEntitiesString($sql, [$parentid]);
	}
}
