<?php
require_once 'config.php';

// Basic authentication
if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== ADMIN_USERNAME || 
    !password_verify($_SERVER['PHP_AUTH_PASS'], ADMIN_PASSWORD_HASH)) {
    
    header('WWW-Authenticate: Basic realm="CityWing Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

class OrderViewer {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function showOrders($filter = 'all', $page = 1) {
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Build query based on filter
        $where = '';
        $params = [];

        if ($filter !== 'all') {
            $where = 'WHERE o.status = :status';
            $params[':status'] = $filter;
        }

        // Get orders
        $sql = "SELECT o.*, 
                       COUNT(oi.id) as item_count,
                       SUM(oi.quantity) as total_quantity
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                {$where}
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        $orders = $this->db->resultSet();

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM orders o {$where}";
        $this->db->query($countSql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $totalOrders = $this->db->single()['total'];
        $totalPages = ceil($totalOrders / $limit);

        $this->renderOrdersPage($orders, $filter, $page, $totalPages, $totalOrders);
    }

    private function renderOrdersPage($orders, $filter, $page, $totalPages, $totalOrders) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Order Management - CityWing Shuttles</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; }
                .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .filters { margin: 20px 0; }
                .filter-btn { padding: 8px 16px; margin: 0 5px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 5px; }
                .filter-btn.active { background: #064b2f; color: white; }
                .orders-table { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
                th { background: #f8f9fa; font-weight: bold; }
                .status-paid { color: #0a6e48; font-weight: bold; }
                .status-pending { color: #d4af37; font-weight: bold; }
                .status-failed { color: #dc3545; font-weight: bold; }
                .pagination { margin: 20px 0; text-align: center; }
                .pagination a { padding: 8px 16px; margin: 0 5px; border: 1px solid #ddd; text-decoration: none; border-radius: 5px; }
                .pagination a.active { background: #064b2f; color: white; }
                .view-btn { padding: 5px 10px; background: #064b2f; color: white; text-decoration: none; border-radius: 3px; font-size: 12px; }
                .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
                .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .stat-number { font-size: 24px; font-weight: bold; color: #064b2f; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>CityWing Shuttles - Order Management</h1>
                    <p>Total Orders: <?php echo $totalOrders; ?></p>
                </div>

                <?php $this->showStats(); ?>

                <div class="filters">
                    <strong>Filter by status:</strong>
                    <?php
                    $filters = [
                        'all' => 'All Orders',
                        'PAID' => 'Paid',
                        'PENDING' => 'Pending',
                        'FAILED' => 'Failed',
                        'CANCELLED' => 'Cancelled'
                    ];
                    foreach ($filters as $key => $label) {
                        $active = $filter === $key ? 'active' : '';
                        echo "<a href='?filter=$key&page=1' class='filter-btn $active'>$label</a>";
                    }
                    ?>
                </div>

                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No orders found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_reference']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['item_count']; ?> items</td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="receipt.php?reference=<?php echo $order['order_reference']; ?>" target="_blank" class="view-btn">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }

    private function showStats() {
        $sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) as paid_orders,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'PAID' THEN total_amount ELSE 0 END) as total_revenue
                FROM orders";
        
        $this->db->query($sql);
        $stats = $this->db->single();
        ?>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div>Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['paid_orders']; ?></div>
                <div>Paid Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                <div>Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo CURRENCY_SYMBOL . number_format($stats['total_revenue'], 2); ?></div>
                <div>Total Revenue</div>
            </div>
        </div>
        <?php
    }
}

$filter = $_GET['filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));

$orderViewer = new OrderViewer();
$orderViewer->showOrders($filter, $page);
?>