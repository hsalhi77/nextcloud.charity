<?php
namespace OCA\Charity\Db;

class cc_Asset extends RelationalEntity {
	protected $refPaymentId;
	protected $caseId;
	protected $assetType;
	protected $datePurchased;
	protected $serial;
	protected $imei;
	protected $warranty;
	protected $model;
	protected $processor;
	protected $paidAmount;
	protected $paymentReference;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('refPaymentId', 'integer');
		$this->addType('caseId', 'integer');
		$this->addType('assetType', 'string');
		$this->addType('datePurchased', 'datetime');
		$this->addType('serial', 'string');
		$this->addType('imei', 'string');
		$this->addType('warranty', 'datetime');
		$this->addType('model', 'string');
		$this->addType('processor', 'string');
		$this->addType('paidAmount', 'float');
		$this->addType('paymentReference', 'string');
	}

	public function jsonSerialize() {
		$json = parent::jsonSerialize();
		if ($this->datePurchased instanceof \DateTime) {
			$json['datePurchased'] = $this->datePurchased->format('Y-m-d');
		}
		if ($this->warranty instanceof \DateTime) {
			$json['warranty'] = $this->warranty->format('Y-m-d');
		}
		return $json;
	}
}
