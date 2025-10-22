<?php
// payment.php - Handle payment processing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once 'config.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $reference_number = $data['reference_number'] ?? '';
    $card_number = $data['card_number'] ?? '';
    $expiry_date = $data['expiry_date'] ?? '';
    $cvv = $data['cvv'] ?? '';
    $card_name = $data['card_name'] ?? '';
    $cart = $data['cart'] ?? [];
    
    if (empty($reference_number) || empty($card_number) || empty($expiry_date) || empty($cvv) || empty($card_name)) {
        echo json_encode(['success' => false, 'message' => 'All payment fields are required']);
        exit;
    }
    
    // Basic card validation
    $card_number = str_replace(' ', '', $card_number);
    if (!preg_match('/^\d{16}$/', $card_number)) {
        echo json_encode(['success' => false, 'message' => 'Invalid card number']);
        exit;
    }
    
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CVV']);
        exit;
    }
    
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
        
        // Find booking
        $query = "SELECT id, total_price FROM bookings WHERE reference_number = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$reference_number]);
        
        if ($stmt->rowCount() === 1) {
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            $booking_id = $booking['id'];
            $amount = $booking['total_price'];
            
            // In a real application, you would integrate with a payment gateway here
            // For demo purposes, we'll simulate a successful payment
            
            $transaction_id = 'TXN' . date('YmdHis') . rand(1000, 9999);
            
            // Insert payment record
            $query = "INSERT INTO payments (
                booking_id, user_id, reference_number, amount, 
                status, transaction_id, payment_data
            ) VALUES (?, ?, ?, ?, 'completed', ?, ?)";
            
            $stmt = $db->prepare($query);
            $payment_data = json_encode([
                'card_last4' => substr($card_number, -4),
                'card_name' => $card_name,
                'payment_method' => 'credit_card'
            ]);
            
            if ($stmt->execute([$booking_id, $user_id, $reference_number, $amount, $transaction_id, $payment_data])) {
                // Update booking status
                $query = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$booking_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'booking_reference' => $reference_number,
                    'transaction_id' => $transaction_id,
                    'amount' => $amount
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Payment processing failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
        }
        
    } catch(PDOException $exception) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $exception->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>