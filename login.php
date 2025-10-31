<?php
// login.php - Simplified for local development
require_once 'config.php';
require_once 'Database.php';

header('Content-Type: application/json');

// Simulate processing delay
sleep(1);

// Get POST data
$email = $_POST['loginEmail'] ?? '';
$password = $_POST['loginPassword'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit;
}

// Mock successful login
$userData = [
    'id' => rand(1000, 9999),
    'name' => explode('@', $email)[0], // Use part of email as name
    'email' => $email,
    'phone' => '+2641234567' // Default phone for local dev
];

// Log the login (for debugging)
error_log("Local Login: " . $email);

// Return success response
echo json_encode([
    'success' => true,
    'user' => $userData,
    'token' => 'local_token_' . uniqid(),
    'message' => 'Login successful! (Local Development Mode)'
]);
?>