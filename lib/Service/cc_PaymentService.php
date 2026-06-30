<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_Payment;
use OCA\Charity\Db\cc_PaymentMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class cc_PaymentService {
	private $mapper;

	public function __construct(cc_PaymentMapper $mapper) {
		$this->mapper = $mapper;
	}

	public function findAll() {
		return $this->mapper->findAll();
	}

	public function find($id) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Payment not found');
		}
		return $item;
	}

	public function findByCase($caseId) {
		return $this->mapper->findByCase($caseId);
	}

	public function create($param) {
		$item = new cc_Payment();
		$item->setCaseId($param['caseId'] ?? null);
		$item->setPaymentDate(isset($param['paymentDate']) ? new \DateTime($param['paymentDate']) : new \DateTime());
		$item->setPaymentReceipt($param['paymentReceipt'] ?? '');
		$item->setPaidBy($param['paidBy'] ?? '');
		$item->setPaymentType($param['paymentType'] ?? '');
		return $this->mapper->insert($item);
	}

	public function update($param, $id) {
		$item = $this->mapper->find($id);
		if (isset($param['caseId'])) $item->setCaseId($param['caseId']);
		if (isset($param['paymentDate'])) $item->setPaymentDate(new \DateTime($param['paymentDate']));
		if (isset($param['paymentReceipt'])) $item->setPaymentReceipt($param['paymentReceipt']);
		if (isset($param['paidBy'])) $item->setPaidBy($param['paidBy']);
		if (isset($param['paymentType'])) $item->setPaymentType($param['paymentType']);
		return $this->mapper->update($item);
	}

	public function delete($id) {
		$item = $this->mapper->find($id);
		return $this->mapper->delete($item);
	}
}
