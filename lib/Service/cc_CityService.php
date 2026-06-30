<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_City;
use OCA\Charity\Db\cc_CityMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class cc_CityService {
	private $mapper;

	public function __construct(cc_CityMapper $mapper) {
		$this->mapper = $mapper;
	}

	public function findAll() {
		return $this->mapper->findAll();
	}

	public function create($title) {
		$item = new cc_City();
		$item->setTitle($title);
		$item->setIsactive(1);
		return $this->mapper->insert($item);
	}

	public function update($id, $title) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('City not found');
		}
		$item->setTitle($title);
		return $this->mapper->update($item);
	}

	public function delete($id) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('City not found');
		}
		return $this->mapper->delete($item);
	}
}
