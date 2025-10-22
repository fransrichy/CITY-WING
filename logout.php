<?php
// logout.php - User logout
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once 'config.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a stateless JWT system, logout is handled client-side by removing the token
    // This endpoint can be used for server-side cleanup if needed
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $payload = JWT_helper::validateToken($token);
        
        if ($payload) {
            // You could add the token to a blacklist here if needed
            echo json_encode(['success' => true, 'message' => 'Logout successful']);
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