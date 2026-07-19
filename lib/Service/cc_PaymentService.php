<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_Payment;
use OCA\Charity\Db\cc_PaymentMapper;
use OCA\Charity\Exceptions\NoPermissionException;
use OCP\AppFramework\Db\DoesNotExistException;

class cc_PaymentService {
	private $mapper;
	private AttachmentService $attachmentService;
	private TeamService $teamService;
	private ?string $userId;

	public function __construct(cc_PaymentMapper $mapper, AttachmentService $attachmentService, TeamService $teamService, $userId) {
		$this->mapper = $mapper;
		$this->attachmentService = $attachmentService;
		$this->teamService = $teamService;
		$this->userId = $userId;
	}

	public function findAll($param = []) {
		return $this->mapper->findAll($param);
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
		$item->setPaymentAmount($param['paymentAmount'] ?? 0);
		$item->setPaymentReference($param['paymentReference'] ?? '');
		return $this->mapper->insert($item);
	}

	public function update($param, $id) {
		$item = $this->mapper->find($id);
		if (isset($param['caseId'])) $item->setCaseId($param['caseId']);
		if (isset($param['paymentDate'])) $item->setPaymentDate(new \DateTime($param['paymentDate']));
		if (isset($param['paymentReceipt'])) $item->setPaymentReceipt($param['paymentReceipt']);
		if (isset($param['paidBy'])) $item->setPaidBy($param['paidBy']);
		if (isset($param['paymentType'])) $item->setPaymentType($param['paymentType']);
		if (isset($param['paymentAmount'])) $item->setPaymentAmount($param['paymentAmount']);
		if (isset($param['paymentReference'])) $item->setPaymentReference($param['paymentReference']);
		return $this->mapper->update($item);
	}

	public function delete($id) {
		if (!$this->teamService->isAdmin()) {
			throw new NoPermissionException('Only Admin and Charity Admin users can delete records.');
		}
		$this->attachmentService->deleteAllForObject($id, 'cc_Payment', $this->userId);
		$this->attachmentService->deleteObjectFolder($id, 'cc_Payment');
		$item = $this->mapper->find($id);
		return $this->mapper->delete($item);
	}
}
