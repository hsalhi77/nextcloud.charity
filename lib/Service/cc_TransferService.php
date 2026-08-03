<?php
namespace OCA\Charity\Service;

use OCA\Charity\Db\cc_Payment;
use OCA\Charity\Db\cc_PaymentMapper;
use OCA\Charity\Db\cc_Transfer;
use OCA\Charity\Db\cc_TransferMapper;
use OCA\Charity\Exceptions\NoPermissionException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;

class cc_TransferService {
    private const TRANSFER_PAYMENT = 'Transfer Payment';
    private const TRANSFER_RECEIPT = 'Transfer Receipt';

    private $mapper;
    private $paymentMapper;
    private $teamService;
    private $db;

    public function __construct(cc_TransferMapper $mapper, cc_PaymentMapper $paymentMapper, TeamService $teamService, IDBConnection $db) {
        $this->mapper = $mapper;
        $this->paymentMapper = $paymentMapper;
        $this->teamService = $teamService;
        $this->db = $db;
    }

    public function findAll($param = []) {
        return $this->mapper->findAll($param);
    }

    public function find($id) {
        $item = $this->mapper->find($id);
        if ($item === null) {
            throw new DoesNotExistException('Transfer not found');
        }
        return $item;
    }

    public function create($param) {
        $paidFrom = $param['paidFrom'] ?? '';
        $paidTo = $param['paidTo'] ?? '';
        $amount = (float)($param['amount'] ?? 0);

        if ($paidFrom === '' || $paidTo === '') {
            throw new \InvalidArgumentException('Paid From and Paid To are required.');
        }
        $memberUids = array_column($this->teamService->getUsersByGroup('Charity Field'), 'uid');
        if (!in_array($paidFrom, $memberUids, true) || !in_array($paidTo, $memberUids, true)) {
            throw new \InvalidArgumentException('Paid From and Paid To must be members of the Charity Field group.');
        }
        if ($paidFrom === $paidTo) {
            throw new \InvalidArgumentException('Paid From and Paid To must be different.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        $this->db->beginTransaction();
        try {
            $transfer = new cc_Transfer();
            $transfer->setTransferDate(isset($param['transferDate']) ? new \DateTime($param['transferDate']) : new \DateTime());
            $transfer->setRef($param['ref'] ?? '');
            $transfer->setDescription($param['description'] ?? '');
            $transfer->setAmount($amount);
            $transfer->setPaidFrom($paidFrom);
            $transfer->setPaidTo($paidTo);
            $this->mapper->insert($transfer);

            $this->insertTransferPayment($transfer->getId(), self::TRANSFER_PAYMENT, $paidFrom, $amount, $transfer->getTransferDate(), $transfer->getRef(), $transfer->getDescription());
            $this->insertTransferPayment($transfer->getId(), self::TRANSFER_RECEIPT, $paidTo, $amount, $transfer->getTransferDate(), $transfer->getRef(), $transfer->getDescription());

            $this->db->commit();
            return $transfer;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete($id) {
        if (!$this->teamService->isAdmin()) {
            throw new NoPermissionException('Only Admin and Charity Admin users can delete records.');
        }
        $this->db->beginTransaction();
        try {
            $item = $this->mapper->find($id);
            if ($item === null) {
                throw new DoesNotExistException('Transfer not found');
            }
            $this->paymentMapper->deleteByTransfer($id);
            $this->mapper->delete($item);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return true;
    }

    private function insertTransferPayment($transferId, string $type, string $paidBy, float $amount, \DateTime $date, string $ref, string $description) {
        $payment = new cc_Payment();
        $payment->setCaseId(null);
        $payment->setPaymentDate($date);
        $payment->setPaymentReceipt('');
        $payment->setPaidBy($paidBy);
        $payment->setPaymentType($type);
        $payment->setPaymentAmount($amount);
        $payment->setPaymentReference($ref);
        $payment->setDescription($description);
        $payment->setTransferId($transferId);
        $this->paymentMapper->insert($payment);
    }
}
