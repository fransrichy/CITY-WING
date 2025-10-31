<?php
require_once 'Database.php';

class PayGateReturn {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function handleReturn() {
        $reference = $_GET['REFERENCE'] ?? '';

        if (empty($reference)) {
            $this->showError('Invalid return data');
            return;
        }

        $order = $this->getOrderDetails($reference);
        if (!$order) {
            $this->showError('Order not found');
            return;
        }

        $this->showOrderStatus($order);
    }

    private function getOrderDetails($reference) {
        $sql = "SELECT o.*, 
                       GROUP_CONCAT(oi.product_name) as items,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.order_reference = :reference
                GROUP BY o.id";
        
        $this->db->query($sql);
        $this->db->bind(':reference', $reference);
        return $this->db->single();
    }

    private function showOrderStatus($order) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Payment Status - CityWing Shuttles</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .status-success { color: #0a6e48; border-left: 4px solid #0a6e48; padding-left: 15px; }
                .status-pending { color: #d4af37; border-left: 4px solid #d4af37; padding-left: 15px; }
                .status-failed { color: #dc3545; border-left: 4px solid #dc3545; padding-left: 15px; }
                .bank-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .btn { display: inline-block; padding: 10px 20px; background: #064b2f; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
                .btn-outline { background: transparent; border: 2px solid #064b2f; color: #064b2f; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Payment Status</h1>
                
                <?php if ($order['status'] === 'PAID'): ?>
                    <div class="status-success">
                        <h2>✅ Payment Successful!</h2>
                        <p>Thank you for your payment. Your booking has been confirmed.</p>
                    </div>
                <?php elseif ($order['status'] === 'PENDING'): ?>
                    <div class="status-pending">
                        <h2>⏳ Payment Processing</h2>
                        <p>Your payment is being processed. Please wait a few moments for confirmation.</p>
                        <p><small>If status doesn't update automatically, please refresh this page.</small></p>
                    </div>
                <?php else: ?>
                    <div class="status-failed">
                        <h2>❌ Payment Failed</h2>
                        <p>Your payment was not successful. Please try again or use alternative payment method.</p>
                    </div>
                <?php endif; ?>

                <div class="order-details">
                    <h3>Order Details</h3>
                    <p><strong>Reference:</strong> <?php echo $order['order_reference']; ?></p>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                    <p><strong>Amount:</strong> <?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></p>
                    <p><strong>Status:</strong> <?php echo $order['status']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                </div>

                <?php if ($order['status'] !== 'PAID'): ?>
                <div class="bank-details">
                    <h3>Alternative Payment Method</h3>
                    <p>You can also pay via bank transfer:</p>
                    <p><strong>Bank:</strong> <?php echo BANK_NAME; ?></p>
                    <p><strong>Account Name:</strong> <?php echo ACCOUNT_NAME; ?></p>
                    <p><strong>Account Type:</strong> <?php echo ACCOUNT_TYPE; ?></p>
                    <p><strong>Account Number:</strong> <?php echo ACCOUNT_NUMBER; ?></p>
                    <p><strong>Reference:</strong> <?php echo $order['order_reference']; ?></p>
                    <p><em>Please email proof of payment to info@citywingshuttles.com</em></p>
                </div>
                <?php endif; ?>

                <div class="actions">
                    <?php if ($order['status'] === 'PAID'): ?>
                        <a href="receipt.php?reference=<?php echo $order['order_reference']; ?>" class="btn" target="_blank">View Receipt</a>
                    <?php endif; ?>
                    <a href="booking.html" class="btn btn-outline">Book Another Service</a>
                    <a href="index.html" class="btn btn-outline">Return to Home</a>
                </div>

                <?php if ($order['status'] === 'PENDING'): ?>
                <script>
                    // Auto-refresh to check for status update
                    setTimeout(() => {
                        location.reload();
                    }, 5000);
                </script>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }

    private function showError($message) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - CityWing Shuttles</title>
        </head>
        <body>
            <div style="max-width: 600px; margin: 50px auto; text-align: center;">
                <h1>Error</h1>
                <p><?php echo $message; ?></p>
                <a href="index.html">Return to Home</a>
            </div>
        </body>
        </html>
        <?php
    }
}

$returnHandler = new PayGateReturn();
$returnHandler->handleReturn();
?>