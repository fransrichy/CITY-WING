<?php
// get_bookings.php - Get user bookings
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once 'config.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $payload = JWT_helper::validateToken($token);
        
        if ($payload) {
            $user_id = $payload['user_id'];
            
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT 
                    b.reference_number, b.full_name, b.email, b.phone, b.service_type,
                    b.passengers, b.arrival_date, b.time, b.pickup_address, b.dropoff_address,
                    b.special_requests, b.status, b.total_price, b.created_at,
                    p.status as payment_status, p.transaction_id
                    FROM bookings b
                    LEFT JOIN payments p ON b.id = p.booking_id
                    WHERE b.user_id = ?
                    ORDER BY b.created_at DESC";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
                
                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'bookings' => $bookings
                ]);
                
            } catch(PDOException $exception) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $exception->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No token provided']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>