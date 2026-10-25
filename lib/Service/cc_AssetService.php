<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_Asset;
use OCA\Charity\Db\cc_AssetMapper;
use OCA\Charity\Db\cc_CaseMapper;
use OCA\Charity\Db\cc_PaymentMapper;
use OCA\Charity\Exceptions\NoPermissionException;

class cc_AssetService {
	private const ASSET_TYPE = 'Asset Purchase';
	private const ASSET_TYPES = ['Phone', 'Laptop', 'Tab', 'Medical', 'Other'];

	private cc_AssetMapper $assetMapper;
	private cc_PaymentMapper $paymentMapper;
	private cc_CaseMapper $caseMapper;
	private TeamService $teamService;

	public function __construct(cc_AssetMapper $assetMapper,
	                            cc_PaymentMapper $paymentMapper,
	                            cc_CaseMapper $caseMapper,
	                            TeamService $teamService) {
		$this->assetMapper = $assetMapper;
		$this->paymentMapper = $paymentMapper;
		$this->caseMapper = $caseMapper;
		$this->teamService = $teamService;
	}

	public function findAll($param = []): array {
		$assets = $this->assetMapper->findAll($param);
		$this->hydratePaymentReferences($assets);
		return $assets;
	}

	public function find($id): ?cc_Asset {
		return $this->assetMapper->find($id);
	}

	public function findByPayment(int $paymentId): array {
		return $this->assetMapper->findByPayment($paymentId);
	}

	public function findByCase(int $caseId): array {
		return $this->assetMapper->findByCase($caseId);
	}

	public function loadAssetsForPayment($payment): void {
		$paymentId = $payment->getId();
		if ($paymentId === null) {
			return;
		}
		$payment->setAssets($this->assetMapper->findByPayment($paymentId));
	}

	public function create(array $param): cc_Asset {
		$paymentId = (int)($param['refPaymentId'] ?? 0);
		$payment = $this->paymentMapper->find($paymentId);
		if ($payment === null) {
			throw new \InvalidArgumentException('Payment not found.');
		}
		if ($payment->getPaymentType() !== self::ASSET_TYPE) {
			throw new \InvalidArgumentException('Assets can only be created for Asset Purchase payments.');
		}
		if (empty($param['assetType'])) {
			throw new \InvalidArgumentException('Asset type is required.');
		}
		if (!isset($param['paidAmount']) || $param['paidAmount'] === '' || $param['paidAmount'] === null) {
			throw new \InvalidArgumentException('Paid amount is required.');
		}

		$asset = new cc_Asset();
		$asset->setRefPaymentId($paymentId);
		$paymentDate = $payment->getPaymentDate();
		if ($paymentDate instanceof \DateTime) {
			$asset->setDatePurchased($paymentDate);
		} elseif (!empty($paymentDate)) {
			$asset->setDatePurchased(new \DateTime($paymentDate));
		}
		$this->applyAssetFields($asset, $param);
		return $this->assetMapper->insert($asset);
	}

	public function update(int $id, array $param): cc_Asset {
		$asset = $this->assetMapper->find($id);
		if ($asset === null) {
			throw new \InvalidArgumentException('Asset not found.');
		}

		if (isset($param['refPaymentId'])) {
			throw new \InvalidArgumentException('Cannot change the parent payment of an asset.');
		}

		$this->applyAssetFields($asset, $param);
		return $this->assetMapper->update($asset);
	}

	public function assignCase(int $id, ?int $caseId): cc_Asset {
		if ($caseId !== null) {
			$case = $this->caseMapper->find($caseId);
			if ($case === null) {
				throw new \InvalidArgumentException('Case not found.');
			}
		}
		return $this->update($id, ['caseId' => $caseId]);
	}

	public function delete(int $id): void {
		if (!$this->teamService->isAdmin()) {
			throw new NoPermissionException('Only Admin and Charity Admin users can delete assets.');
		}
		$asset = $this->assetMapper->find($id);
		if ($asset === null) {
			throw new \InvalidArgumentException('Asset not found.');
		}
		$this->assetMapper->delete($asset);
	}

	public function deleteByPayment(int $paymentId): void {
		$this->assetMapper->deleteByPayment($paymentId);
	}

	private function hydratePaymentReferences(array $assets): void {
		foreach ($assets as $asset) {
			$payment = $this->paymentMapper->find($asset->getRefPaymentId());
			$asset->setPaymentReference($payment ? $payment->getPaymentReference() : '');
		}
	}

	private function applyAssetFields(cc_Asset $asset, array $param): void {
		if (array_key_exists('caseId', $param)) {
			$caseId = $param['caseId'];
			if ($caseId !== null && $caseId !== '') {
				$case = $this->caseMapper->find((int)$caseId);
				if ($case === null) {
					throw new \InvalidArgumentException('Case not found.');
				}
				$asset->setCaseId((int)$caseId);
			} else {
				$asset->setCaseId(null);
			}
		}
		if (isset($param['assetType'])) {
			$type = $param['assetType'];
			if (!in_array($type, self::ASSET_TYPES, true)) {
				throw new \InvalidArgumentException('Invalid asset type: ' . $type);
			}
			$asset->setAssetType($type);
		}
		if (array_key_exists('serial', $param)) {
			$asset->setSerial($param['serial'] ?? '');
		}
		if (array_key_exists('imei', $param)) {
			$asset->setImei($param['imei'] ?? '');
		}
		if (isset($param['warranty'])) {
			$asset->setWarranty(!empty($param['warranty']) ? new \DateTime($param['warranty']) : null);
		}
		if (array_key_exists('model', $param)) {
			$asset->setModel($param['model'] ?? '');
		}
		if (array_key_exists('processor', $param)) {
			$asset->setProcessor($param['processor'] ?? '');
		}
		if (isset($param['paidAmount'])) {
			$asset->setPaidAmount((float)$param['paidAmount']);
		}
	}
}
