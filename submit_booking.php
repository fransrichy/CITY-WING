<?php
// submit_booking.php - Handle booking submissions
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once 'config.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Service pricing
    $servicePrices = [
        'airport-transfer' => 45,
        'city-tour' => 75,
        'safari-tour' => 220,
        'coastal-tour' => 150,
        'private-shuttle' => 90,
        'corporate-service' => 120,
        'special-package' => 650
    ];
    
    // Extract booking data
    $full_name = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $country_code = $data['country_code'] ?? '+264';
    $service_type = $data['service_type'] ?? '';
    $passengers = intval($data['passengers'] ?? 1);
    $arrival_date = $data['arrival_date'] ?? '';
    $time = $data['time'] ?? '';
    $pickup_address = $data['pickup_address'] ?? '';
    $dropoff_address = $data['dropoff_address'] ?? '';
    $special_requests = $data['special_requests'] ?? '';
    
    // Validate required fields
    $required_fields = ['full_name', 'email', 'phone', 'service_type', 'arrival_date', 'time', 'pickup_address', 'dropoff_address'];
    foreach ($required_fields as $field) {
        if (empty($$field)) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Calculate price
    $base_price = $servicePrices[$service_type] ?? 0;
    $passenger_multiplier = $passengers > 4 ? 1.2 : 1;
    $total_price = $base_price * $passenger_multiplier;
    
    // Generate reference number
    $reference_number = 'CW' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get user ID from token if available
        $user_id = null;
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = JWT_helper::validateToken($token);
            if ($payload) {
                $user_id = $payload['user_id'];
            }
        }
        
        // Insert booking
        $query = "INSERT INTO bookings (
            user_id, reference_number, full_name, email, phone, country_code, 
            service_type, passengers, arrival_date, time, pickup_address, 
            dropoff_address, special_requests, total_price
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            $user_id, $reference_number, $full_name, $email, $phone, $country_code,
            $service_type, $passengers, $arrival_date, $time, $pickup_address,
            $dropoff_address, $special_requests, $total_price
        ])) {
            $booking_id = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking submitted successfully',
                'booking_reference' => $reference_number,
                'booking_id' => $booking_id,
                'total_price' => $total_price
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit booking']);
        }
        
    } catch(PDOException $exception) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $exception->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>