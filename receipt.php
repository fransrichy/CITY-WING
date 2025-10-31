<?php
require_once 'Database.php';

class ReceiptGenerator {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function generateReceipt($reference) {
        $order = $this->getOrderWithItems($reference);
        if (!$order) {
            $this->showError('Receipt not found');
            return;
        }

        $this->showReceipt($order);
    }

    private function getOrderWithItems($reference) {
        // Get order details
        $sql = "SELECT * FROM orders WHERE order_reference = :reference";
        $this->db->query($sql);
        $this->db->bind(':reference', $reference);
        $order = $this->db->single();

        if (!$order) return null;

        // Get order items
        $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
        $this->db->query($sql);
        $this->db->bind(':order_id', $order['id']);
        $order['items'] = $this->db->resultSet();

        return $order;
    }

    private function showReceipt($order) {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Receipt - <?php echo $order['order_reference']; ?> - CityWing Shuttles</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
                .receipt { max-width: 800px; margin: 0 auto; border: 2px solid #064b2f; padding: 30px; }
                .header { text-align: center; border-bottom: 2px solid #064b2f; padding-bottom: 20px; margin-bottom: 30px; }
                .company-name { color: #064b2f; font-size: 24px; font-weight: bold; margin: 0; }
                .receipt-title { font-size: 20px; margin: 10px 0; }
                .section { margin-bottom: 25px; }
                .section-title { background: #064b2f; color: white; padding: 8px 15px; margin: 0 -30px 15px -30px; }
                .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .items-table th { background: #f5f5f5; text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
                .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
                .total-row { font-weight: bold; border-top: 2px solid #064b2f; }
                .bank-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                    .receipt { border: none; padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="header">
                    <h1 class="company-name">CITYWING SHUTTLES</h1>
                    <h2 class="receipt-title">OFFICIAL RECEIPT</h2>
                    <p>Premium Transportation Services</p>
                </div>

                <div class="section">
                    <div class="section-title">Order Information</div>
                    <div class="row">
                        <span><strong>Receipt Number:</strong></span>
                        <span><?php echo $order['order_reference']; ?></span>
                    </div>
                    <div class="row">
                        <span><strong>Date Issued:</strong></span>
                        <span><?php echo date('F j, Y, g:i a', strtotime($order['transaction_date'] ?: $order['created_at'])); ?></span>
                    </div>
                    <div class="row">
                        <span><strong>Payment Status:</strong></span>
                        <span style="color: <?php echo $order['status'] === 'PAID' ? '#0a6e48' : '#dc3545'; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <?php if ($order['paygate_reference']): ?>
                    <div class="row">
                        <span><strong>Payment Reference:</strong></span>
                        <span><?php echo $order['paygate_reference']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <div class="section-title">Customer Details</div>
                    <div class="row">
                        <span><strong>Name:</strong></span>
                        <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    </div>
                    <div class="row">
                        <span><strong>Email:</strong></span>
                        <span><?php echo htmlspecialchars($order['customer_email']); ?></span>
                    </div>
                    <div class="row">
                        <span><strong>Phone:</strong></span>
                        <span><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Items</div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo CURRENCY_SYMBOL . number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo CURRENCY_SYMBOL . number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                                <td><strong><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bank-details">
                    <h3>Banking Details</h3>
                    <p><strong>Bank:</strong> <?php echo BANK_NAME; ?></p>
                    <p><strong>Account Name:</strong> <?php echo ACCOUNT_NAME; ?></p>
                    <p><strong>Account Type:</strong> <?php echo ACCOUNT_TYPE; ?></p>
                    <p><strong>Account Number:</strong> <?php echo ACCOUNT_NUMBER; ?></p>
                </div>

                <div class="footer">
                    <p>Thank you for choosing CityWing Shuttles!</p>
                    <p>For inquiries: info@citywingshuttles.com | +264 81 234 5678</p>
                    <p>This is an computer-generated receipt. No signature required.</p>
                </div>

                <div class="no-print" style="text-align: center; margin-top: 20px;">
                    <button onclick="window.print()" class="btn">Print Receipt</button>
                    <a href="index.html" class="btn">Return to Home</a>
                </div>
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
                <h1>Receipt Not Found</h1>
                <p><?php echo $message; ?></p>
                <a href="index.html">Return to Home</a>
            </div>
        </body>
        </html>
        <?php
    }
}

$reference = $_GET['reference'] ?? '';
if (empty($reference)) {
    header('Location: index.html');
    exit;
}

$receiptGenerator = new ReceiptGenerator();
$receiptGenerator->generateReceipt($reference);
?>