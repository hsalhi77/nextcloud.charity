<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_CaseType;
use OCA\Charity\Db\cc_CaseTypeMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class cc_CaseTypeService {
	private $mapper;

	public function __construct(cc_CaseTypeMapper $mapper) {
		$this->mapper = $mapper;
	}

	public function findAll() {
		return $this->mapper->findAll();
	}

	public function create($title) {
		$item = new cc_CaseType();
		$item->setTitle($title);
		$item->setIsactive(1);
		return $this->mapper->insert($item);
	}

	public function update($id, $title) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Case type not found');
		}
		$item->setTitle($title);
		return $this->mapper->update($item);
	}

	public function delete($id) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Case type not found');
		}
		return $this->mapper->delete($item);
	}
}
