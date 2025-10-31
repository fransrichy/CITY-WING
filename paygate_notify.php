<?php
require_once 'Database.php';

class PayGateNotify {
    private $db;
    private $encryptionKey;

    public function __construct() {
        $this->db = new Database();
        $this->encryptionKey = PAYGATE_ENCRYPTION_KEY;
    }

    public function handleNotification() {
        // Log the raw POST data
        $postData = $_POST;
        $this->logNotification('IPN Received', $postData);

        // Verify checksum
        if (!$this->verifyChecksum($postData)) {
            $this->logNotification('Checksum verification failed', $postData);
            http_response_code(400);
            echo 'Checksum verification failed';
            return;
        }

        // Process the notification
        $this->processPayment($postData);

        // Always return OK to PayGate
        echo 'OK';
    }

    private function verifyChecksum($data) {
        $checksum = $data['CHECKSUM'];
        unset($data['CHECKSUM']);

        // Sort data alphabetically for checksum calculation
        ksort($data);
        $checksumData = implode('', $data);
        $checksumData .= $this->encryptionKey;
        $calculatedChecksum = md5($checksumData);

        return $checksum === $calculatedChecksum;
    }

    private function processPayment($data) {
        $orderReference = $data['REFERENCE'];
        $paygateId = $data['PAY_REQUEST_ID'];
        $transactionStatus = $data['TRANSACTION_STATUS'];
        $resultCode = $data['RESULT_CODE'];
        $resultDesc = $data['RESULT_DESC'];

        try {
            // Find order by reference
            $order = $this->getOrderByReference($orderReference);
            if (!$order) {
                $this->logNotification('Order not found: ' . $orderReference, $data);
                return;
            }

            // Update order status based on transaction status
            $newStatus = $this->mapTransactionStatus($transactionStatus, $resultCode);
            
            $this->updateOrderStatus($order['id'], $newStatus, [
                'paygate_payment_id' => $paygateId,
                'paygate_reference' => $data['PAYMENT_REFERENCE'] ?? null,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'payment_method' => $data['PAYMENT_METHOD'] ?? null,
                'transaction_date' => date('Y-m-d H:i:s')
            ]);

            $this->logNotification("Order {$orderReference} updated to {$newStatus}", $data, $order['id']);

        } catch (Exception $e) {
            $this->logNotification('Error processing payment: ' . $e->getMessage(), $data);
        }
    }

    private function getOrderByReference($reference) {
        $sql = "SELECT * FROM orders WHERE order_reference = :reference";
        $this->db->query($sql);
        $this->db->bind(':reference', $reference);
        return $this->db->single();
    }

    private function mapTransactionStatus($transactionStatus, $resultCode) {
        // PayGate status mapping
        switch ($transactionStatus) {
            case '1': // Completed
                return $resultCode == '990017' ? 'PAID' : 'FAILED';
            case '2': // Cancelled
                return 'CANCELLED';
            case '0': // Not Done
            case '4': // User Cancelled
            default:
                return 'FAILED';
        }
    }

    private function updateOrderStatus($orderId, $status, $paymentData) {
        $sql = "UPDATE orders SET 
                status = :status,
                paygate_payment_id = :paygate_payment_id,
                paygate_reference = :paygate_reference,
                payment_method = :payment_method,
                transaction_date = :transaction_date,
                updated_at = NOW()
                WHERE id = :order_id";
        
        $this->db->query($sql);
        $this->db->bind(':status', $status);
        $this->db->bind(':paygate_payment_id', $paymentData['paygate_payment_id']);
        $this->db->bind(':paygate_reference', $paymentData['paygate_reference']);
        $this->db->bind(':payment_method', $paymentData['payment_method']);
        $this->db->bind(':transaction_date', $paymentData['transaction_date']);
        $this->db->bind(':order_id', $orderId);
        $this->db->execute();
    }

    private function logNotification($message, $data, $orderId = null) {
        $sql = "INSERT INTO payment_logs (order_id, log_type, message, data, ip_address) 
                VALUES (:order_id, 'NOTIFICATION', :message, :data, :ip_address)";
        
        $this->db->query($sql);
        $this->db->bind(':order_id', $orderId);
        $this->db->bind(':message', $message);
        $this->db->bind(':data', json_encode($data));
        $this->db->bind(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        $this->db->execute();

        // Also log to file
        error_log("[PayGate IPN] {$message}: " . json_encode($data));
    }
}

// Handle the notification
$notifyHandler = new PayGateNotify();
$notifyHandler->handleNotification();
?>