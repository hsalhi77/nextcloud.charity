<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_UpdateType;
use OCA\Charity\Db\cc_UpdateTypeMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class cc_UpdateTypeService {
	private $mapper;

	public function __construct(cc_UpdateTypeMapper $mapper) {
		$this->mapper = $mapper;
	}

	public function findAll() {
		return $this->mapper->findAll();
	}

	public function create($title) {
		$item = new cc_UpdateType();
		$item->setTitle($title);
		$item->setIsactive(1);
		return $this->mapper->insert($item);
	}

	public function update($id, $title) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Update type not found');
		}
		$item->setTitle($title);
		return $this->mapper->update($item);
	}

	public function delete($id) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Update type not found');
		}
		return $this->mapper->delete($item);
	}
}
