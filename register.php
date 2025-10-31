<?php
// register.php - Simplified for local development
require_once 'config.php';
require_once 'Database.php';

header('Content-Type: application/json');

// Simulate processing delay
sleep(1);

// Get POST data
$name = $_POST['registerName'] ?? '';
$email = $_POST['registerEmail'] ?? '';
$phone = $_POST['registerPhone'] ?? '';
$password = $_POST['registerPassword'] ?? '';
$confirmPassword = $_POST['registerConfirmPassword'] ?? '';

// Validate input
$errors = [];

if (empty($name)) {
    $errors[] = 'Full name is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required';
}

if (empty($password)) {
    $errors[] = 'Password is required';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match';
}

// If there are errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Mock successful registration
$userData = [
    'id' => rand(1000, 9999),
    'name' => htmlspecialchars($name),
    'email' => $email,
    'phone' => $phone,
    'created_at' => date('Y-m-d H:i:s')
];

// Log the registration (for debugging)
error_log("Local Registration: " . $email);

// Return success response
echo json_encode([
    'success' => true,
    'user' => $userData,
    'token' => 'local_token_' . uniqid(),
    'message' => 'Registration successful! (Local Development Mode)'
]);
?>