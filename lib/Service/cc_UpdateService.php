<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_Update;
use OCA\Charity\Db\cc_UpdateMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class cc_UpdateService {
	private $mapper;
	private AttachmentService $attachmentService;
	private ?string $userId;

	public function __construct(cc_UpdateMapper $mapper, AttachmentService $attachmentService, $userId) {
		$this->mapper = $mapper;
		$this->attachmentService = $attachmentService;
		$this->userId = $userId;
	}

	public function findAll() {
		return $this->mapper->findAll();
	}

	public function find($id) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Update not found');
		}
		return $item;
	}

	public function findByCase($caseId) {
		return $this->mapper->findByCase($caseId);
	}

	public function create($param) {
		$item = new cc_Update();
		$item->setCaseId($param['caseId'] ?? null);
		$item->setUpdateDate(isset($param['updateDate']) ? new \DateTime($param['updateDate']) : new \DateTime());
		$item->setUpdateTypeId($param['updateTypeId'] ?? null);
		$item->setUpdateBy($param['updateBy'] ?? '');
		$item->setDescription($param['description'] ?? '');
		return $this->mapper->insert($item);
	}

	public function update($param, $id) {
		$item = $this->mapper->find($id);
		if (isset($param['caseId'])) $item->setCaseId($param['caseId']);
		if (isset($param['updateDate'])) $item->setUpdateDate(new \DateTime($param['updateDate']));
		if (isset($param['updateTypeId'])) $item->setUpdateTypeId($param['updateTypeId']);
		if (isset($param['updateBy'])) $item->setUpdateBy($param['updateBy']);
		if (isset($param['description'])) $item->setDescription($param['description']);
		return $this->mapper->update($item);
	}

	public function delete($id) {
		$this->attachmentService->deleteAllForObject($id, 'cc_Update', $this->userId);
		$this->attachmentService->deleteObjectFolder($id, 'cc_Update', $this->userId);
		$item = $this->mapper->find($id);
		return $this->mapper->delete($item);
	}
}
