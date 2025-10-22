<?php
// contact.php - Handle contact form submissions
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $subject = $data['subject'] ?? '';
    $message = $data['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // In a real application, you would send an email here
    // For demo purposes, we'll just return success
    
    // Simulate email sending
    $to = "info@citywingshuttles.com";
    $email_subject = "Contact Form: $subject";
    $email_body = "
        Name: $name
        Email: $email
        Phone: $phone
        Subject: $subject
        Message: $message
    ";
    
    // You would use mail() function here in a real application
    // mail($to, $email_subject, $email_body);
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message. We will get back to you soon!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>