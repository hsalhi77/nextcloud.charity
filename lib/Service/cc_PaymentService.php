<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_Payment;
use OCA\Charity\Db\cc_PaymentMapper;
use OCA\Charity\Exceptions\NoPermissionException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception as DBException;
use OCP\IDBConnection;

class cc_PaymentService {
	private const TRANSFER_TYPES = ['Transfer Payment', 'Transfer Receipt'];
	private const ASSET_TYPE = 'Asset Purchase';

	private $mapper;
	private AttachmentService $attachmentService;
	private TeamService $teamService;
	private cc_AssetService $assetService;
	private IDBConnection $db;
	private ?string $userId;

	public function __construct(cc_PaymentMapper $mapper,
	                            AttachmentService $attachmentService,
	                            TeamService $teamService,
	                            cc_AssetService $assetService,
	                            IDBConnection $db,
	                            $userId) {
		$this->mapper = $mapper;
		$this->attachmentService = $attachmentService;
		$this->teamService = $teamService;
		$this->assetService = $assetService;
		$this->userId = $userId;
		$this->db = $db;
	}

	public function findAll($param = []) {
		return $this->mapper->findAll($param);
	}

	public function find($id) {
		$item = $this->mapper->find($id);
		if ($item === null) {
			throw new DoesNotExistException('Payment not found');
		}
		$this->loadAssets($item);
		return $item;
	}

	public function findByCase($caseId) {
		return $this->mapper->findByCase($caseId);
	}

	private function assertManualPaymentType(string $type): void {
		if (in_array($type, self::TRANSFER_TYPES, true)) {
			throw new \InvalidArgumentException('Transfer payment types cannot be created directly.');
		}
	}

	private function assertNotTransferLinked(cc_Payment $item): void {
		if ($item->getTransferId() !== null) {
			throw new \InvalidArgumentException('Transfer payment rows cannot be edited or deleted directly.');
		}
	}

	private function buildPaymentFromParams(cc_Payment $item, array $param): void {
		if (isset($param['caseId'])) $item->setCaseId($param['caseId']);
		if (isset($param['paymentDate'])) $item->setPaymentDate(new \DateTime($param['paymentDate']));
		if (isset($param['paymentReceipt'])) $item->setPaymentReceipt($param['paymentReceipt']);
		if (isset($param['paidBy'])) $item->setPaidBy($param['paidBy']);
		if (isset($param['paymentType'])) {
			$this->assertManualPaymentType($param['paymentType']);
			$item->setPaymentType($param['paymentType']);
		}
		if (isset($param['paymentAmount'])) $item->setPaymentAmount($param['paymentAmount']);
		if (isset($param['paymentReference'])) $item->setPaymentReference($param['paymentReference']);
		if (array_key_exists('description', $param)) $item->setDescription($param['description']);
	}

	private function inTransaction(callable $fn) {
		$this->db->beginTransaction();
		try {
			$result = $fn();
			$this->db->commit();
			return $result;
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	private function loadAssets(cc_Payment $payment): void {
		$this->assetService->loadAssetsForPayment($payment);
	}

	private function createPayment(array $param): cc_Payment {
		$item = new cc_Payment();
		$this->buildPaymentFromParams($item, $param);
		return $this->mapper->insert($item);
	}

	public function create($param) {
		return $this->inTransaction(function () use ($param) {
			return $this->createPayment($param);
		});
	}

	public function createWithAssets(array $paymentData, array $assets): cc_Payment {
		return $this->inTransaction(function () use ($paymentData, $assets) {
			if (empty($paymentData['paymentType']) || $paymentData['paymentType'] !== self::ASSET_TYPE) {
				throw new \InvalidArgumentException('createWithAssets is only allowed for Asset Purchase payments.');
			}
			if (empty($assets)) {
				throw new \InvalidArgumentException('At least one asset is required.');
			}

			$expected = (float)($paymentData['paymentAmount'] ?? 0);
			$total = 0.0;
			foreach ($assets as $asset) {
				$total += (float)($asset['paidAmount'] ?? 0);
			}
			if (round($expected * 100) !== round($total * 100)) {
				throw new \InvalidArgumentException(
					sprintf('Asset total %.2f does not match payment amount %.2f.', $total, $expected)
				);
			}

			$payment = $this->createPayment($paymentData);
			foreach ($assets as $assetParam) {
				$assetParam['refPaymentId'] = $payment->getId();
				$this->assetService->create($assetParam);
			}
			$this->loadAssets($payment);
			return $payment;
		});
	}

	public function update($param, $id) {
		return $this->inTransaction(function () use ($param, $id) {
			$item = $this->find($id);
			$this->assertNotTransferLinked($item);
			$this->buildPaymentFromParams($item, $param);
			return $this->mapper->update($item);
		});
	}

	public function delete($id) {
		if (!$this->teamService->isAdmin()) {
			throw new NoPermissionException('Only Admin and Charity Admin users can delete records.');
		}
		return $this->inTransaction(function () use ($id) {
			$item = $this->find($id);
			$this->assertNotTransferLinked($item);
			$this->assetService->deleteByPayment($item->getId());
			$this->attachmentService->deleteAllForObject($id, 'cc_Payment', $this->userId);
			$this->attachmentService->deleteObjectFolder($id, 'cc_Payment');
			return $this->mapper->delete($item);
		});
	}
}
