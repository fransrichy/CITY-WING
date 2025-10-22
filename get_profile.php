<?php
// get_profile.php - Get user profile
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
                
                $query = "SELECT id, name, email, phone, created_at FROM users WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
                
                if ($stmt->rowCount() === 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get booking count
                    $query = "SELECT COUNT(*) as booking_count FROM bookings WHERE user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    $booking_count = $stmt->fetch(PDO::FETCH_ASSOC)['booking_count'];
                    
                    $user['booking_count'] = $booking_count;
                    
                    echo json_encode([
                        'success' => true,
                        'user' => $user
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                
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