<?php
session_start();
require_once 'Database.php';

class CheckoutHandler {
    private $db;
    private $paygateId;
    private $encryptionKey;
    private $paygateUrl;

    public function __construct() {
        $this->db = new Database();
        $this->paygateId = PAYGATE_ID;
        $this->encryptionKey = PAYGATE_ENCRYPTION_KEY;
        $this->paygateUrl = PAYGATE_URL;
    }

    public function processCheckout($postData) {
        // Validate input data
        $validation = $this->validateInput($postData);
        if (!$validation['success']) {
            return $validation;
        }

        try {
            $this->db->beginTransaction();

            // Create order reference
            $orderReference = $this->generateOrderReference();

            // Calculate total amount
            $totalAmount = $this->calculateTotal($postData['items']);

            // Insert order into database
            $orderId = $this->createOrder($orderReference, $postData, $totalAmount);

            // Insert order items
            $this->createOrderItems($orderId, $postData['items']);

            // Prepare PayGate data
            $paygateData = $this->preparePayGateData($orderReference, $totalAmount, $postData);

            // Log the request
            $this->logPayment($orderId, 'REQUEST', 'Checkout initiated', $paygateData);

            $this->db->commit();

            return [
                'success' => true,
                'order_reference' => $orderReference,
                'paygate_data' => $paygateData,
                'paygate_url' => $this->paygateUrl
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logError('Checkout failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Checkout processing failed. Please try again.'
            ];
        }
    }

    private function validateInput($data) {
        $errors = [];

        // Validate customer details
        if (empty($data['customer_name']) || strlen($data['customer_name']) < 2) {
            $errors[] = 'Valid customer name is required';
        }

        if (empty($data['customer_email']) || !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required';
        }

        if (empty($data['customer_phone'])) {
            $errors[] = 'Phone number is required';
        }

        // Validate items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'No items in cart';
        } else {
            foreach ($data['items'] as $item) {
                if (empty($item['product_name']) || empty($item['unit_price']) || empty($item['quantity'])) {
                    $errors[] = 'Invalid item data';
                    break;
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(', ', $errors)
            ];
        }

        return ['success' => true];
    }

    private function generateOrderReference() {
        return 'CW' . date('Ymd') . strtoupper(uniqid());
    }

    private function calculateTotal($items) {
        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item['unit_price']) * intval($item['quantity']);
        }
        return $total;
    }

    private function createOrder($orderReference, $data, $totalAmount) {
        $sql = "INSERT INTO orders (order_reference, customer_name, customer_email, customer_phone, total_amount, currency, status) 
                VALUES (:order_reference, :customer_name, :customer_email, :customer_phone, :total_amount, :currency, 'PENDING')";
        
        $this->db->query($sql);
        $this->db->bind(':order_reference', $orderReference);
        $this->db->bind(':customer_name', htmlspecialchars($data['customer_name']));
        $this->db->bind(':customer_email', htmlspecialchars($data['customer_email']));
        $this->db->bind(':customer_phone', htmlspecialchars($data['customer_phone']));
        $this->db->bind(':total_amount', $totalAmount);
        $this->db->bind(':currency', CURRENCY);
        $this->db->execute();

        return $this->db->lastInsertId();
    }

    private function createOrderItems($orderId, $items) {
        $sql = "INSERT INTO order_items (order_id, product_type, product_id, product_name, quantity, unit_price, total_price) 
                VALUES (:order_id, :product_type, :product_id, :product_name, :quantity, :unit_price, :total_price)";
        
        foreach ($items as $item) {
            $this->db->query($sql);
            $this->db->bind(':order_id', $orderId);
            $this->db->bind(':product_type', $item['product_type'] ?? 'shuttle_service');
            $this->db->bind(':product_id', $item['product_id'] ?? '');
            $this->db->bind(':product_name', htmlspecialchars($item['product_name']));
            $this->db->bind(':quantity', intval($item['quantity']));
            $this->db->bind(':unit_price', floatval($item['unit_price']));
            $this->db->bind(':total_price', floatval($item['unit_price']) * intval($item['quantity']));
            $this->db->execute();
        }
    }

    private function preparePayGateData($orderReference, $totalAmount, $data) {
        // PayGate requires amount in cents
        $amount = intval($totalAmount * 100);

        $paygateData = [
            'PAYGATE_ID' => $this->paygateId,
            'REFERENCE' => $orderReference,
            'AMOUNT' => $amount,
            'CURRENCY' => CURRENCY,
            'RETURN_URL' => SITE_URL . '/paygate_return.php',
            'TRANSACTION_DATE' => date('Y-m-d H:i:s'),
            'LOCALE' => 'en-za',
            'COUNTRY' => 'ZAF',
            'EMAIL' => $data['customer_email'],
            'NOTIFY_URL' => SITE_URL . '/paygate_notify.php'
        ];

        // Generate checksum - IMPORTANT: Fields must be in alphabetical order
        ksort($paygateData);
        $checksumData = implode('', $paygateData);
        $checksumData .= $this->encryptionKey;
        $paygateData['CHECKSUM'] = md5($checksumData);

        return $paygateData;
    }

    private function logPayment($orderId, $type, $message, $data = null) {
        $sql = "INSERT INTO payment_logs (order_id, log_type, message, data, ip_address) 
                VALUES (:order_id, :log_type, :message, :data, :ip_address)";
        
        $this->db->query($sql);
        $this->db->bind(':order_id', $orderId);
        $this->db->bind(':log_type', $type);
        $this->db->bind(':message', $message);
        $this->db->bind(':data', $data ? json_encode($data) : null);
        $this->db->bind(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        $this->db->execute();
    }

    private function logError($message) {
        error_log("[Checkout Error] " . $message);
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $checkoutHandler = new CheckoutHandler();
    $result = $checkoutHandler->processCheckout($_POST);
    
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Payment Gateway - CityWing Shuttles</title>
    <style>
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            flex-direction: column;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #064b2f;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Redirecting to secure payment gateway...</p>
    </div>

    <?php if (isset($paygateData)): ?>
    <form id="paygateForm" action="<?php echo $this->paygateUrl; ?>" method="POST">
        <?php foreach ($paygateData as $key => $value): ?>
            <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
    </form>
    <script>
        document.getElementById('paygateForm').submit();
    </script>
    <?php endif; ?>
</body>
</html>