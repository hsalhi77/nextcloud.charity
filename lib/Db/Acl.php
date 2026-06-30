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

class Acl extends RelationalEntity {
	public const PERMISSION_READ = 0;
	public const PERMISSION_EDIT = 1;
	public const PERMISSION_SHARE = 2;
	public const PERMISSION_MANAGE = 3;

	public const PERMISSION_TYPE_USER = 0;
	public const PERMISSION_TYPE_GROUP = 1;
	public const PERMISSION_TYPE_CIRCLE = 7;

	protected $parentid;
	protected $description;
	protected $created;
	protected $updated;
	protected $deleted;
	protected $isactive;
	protected $objectId;
	protected $objectType;
	protected $shareid;
	protected $participant;
	protected $type;
	protected $permissionEdit = false;
	protected $permissionShare = false;
	protected $permissionManage = false;
	protected $owner = false;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('parentid', 'integer');
		$this->addType('description', 'string');
		$this->addType('created', 'date');
		$this->addType('updated', 'datetime');
		$this->addType('deleted', 'datetime');
		$this->addType('isactive', 'boolean');
		$this->addType('objectId', 'integer');
		$this->addType('objectType', 'string');
		$this->addType('shareid', 'integer');
		$this->addType('permissionEdit', 'boolean');
		$this->addType('permissionShare', 'boolean');
		$this->addType('permissionManage', 'boolean');
		$this->addType('type', 'integer');
		$this->addResolvable('participant');
	}

	public function getPermission($permission) {
		switch ($permission) {
			case self::PERMISSION_READ:
				return true;
			case self::PERMISSION_EDIT:
				return $this->getpermissionEdit();
			case self::PERMISSION_SHARE:
				return $this->getpermissionShare();
			case self::PERMISSION_MANAGE:
				return $this->getpermissionManage();
		}
		return false;
	}
}
