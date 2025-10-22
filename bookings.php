<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createBooking();
            break;
        case 'get_user_bookings':
            getUserBookings();
            break;
        case 'get_by_reference':
            getBookingByReference();
            break;
        default:
            sendError('Invalid action');
    }
}

function createBooking() {
    $user = getAuthenticatedUser();
    $userId = $user ? $user['user_id'] : null;
    
    $required = ['full_name', 'email', 'phone', 'service_type', 'passengers', 'arrival_date', 'time', 'pickup_address', 'dropoff_address'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            sendError("$field is required");
        }
    }
    
    $db = getDB();
    
    // Generate unique reference number
    $referenceNumber = generateReferenceNumber();
    
    // Calculate total amount based on service type
    $serviceType = $_POST['service_type'];
    $passengers = intval($_POST['passengers']);
    $totalAmount = calculateBookingAmount($serviceType, $passengers);
    
    $stmt = $db->prepare("
        INSERT INTO bookings (
            reference_number, user_id, service_type, full_name, email, phone, country_code,
            passengers, arrival_date, time, pickup_address, dropoff_address, special_requests, total_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $referenceNumber,
        $userId,
        $serviceType,
        sanitizeInput($_POST['full_name']),
        sanitizeInput($_POST['email']),
        sanitizeInput($_POST['phone']),
        sanitizeInput($_POST['country_code'] ?? '+264'),
        $passengers,
        $_POST['arrival_date'],
        $_POST['time'],
        sanitizeInput($_POST['pickup_address']),
        sanitizeInput($_POST['dropoff_address']),
        sanitizeInput($_POST['special_requests'] ?? ''),
        $totalAmount
    ]);
    
    if ($success) {
        $bookingId = $db->lastInsertId();
        
        // Send confirmation email (simulated)
        sendBookingConfirmationEmail($_POST['email'], $referenceNumber, $_POST['full_name']);
        
        sendResponse([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking' => [
                'id' => $bookingId,
                'reference_number' => $referenceNumber,
                'total_amount' => $totalAmount
            ]
        ]);
    } else {
        sendError('Failed to create booking');
    }
}

function getUserBookings() {
    $user = getAuthenticatedUser();
    
    if (!$user) {
        sendError('Authentication required', 401);
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM bookings 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(['bookings' => $bookings]);
}

function getBookingByReference() {
    $reference = $_GET['reference'] ?? '';
    
    if (empty($reference)) {
        sendError('Reference number is required');
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM bookings WHERE reference_number = ?");
    $stmt->execute([$reference]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendError('Booking not found', 404);
    }
    
    sendResponse(['booking' => $booking]);
}

function calculateBookingAmount($serviceType, $passengers) {
    $basePrices = [
        'airport-transfer' => 45,
        'city-tour' => 75,
        'safari-tour' => 220,
        'coastal-tour' => 150,
        'private-shuttle' => 90,
        'corporate-service' => 120,
        'special-package' => 650
    ];
    
    $basePrice = $basePrices[$serviceType] ?? 50;
    
    // Apply surcharge for larger groups
    if ($passengers > 4) {
        $basePrice *= 1.2; // 20% surcharge
    }
    
    return $basePrice;
}

function sendBookingConfirmationEmail($email, $reference, $name) {
    // In a real application, you would use PHPMailer or similar
    $subject = "Booking Confirmation - CityWing Shuttles";
    $message = "
        Dear $name,
        
        Thank you for booking with CityWing Shuttles!
        
        Your booking reference number is: $reference
        
        We will contact you shortly to confirm the details of your booking.
        
        Best regards,
        CityWing Shuttles Team
    ";
    
    // For demo purposes, we'll just log this
    error_log("Email sent to $email: $subject");
    
    return true;
}
?>