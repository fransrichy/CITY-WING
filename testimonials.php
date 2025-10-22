<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    getTestimonials();
}

function getTestimonials() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM testimonials WHERE is_active = TRUE ORDER BY created_at DESC");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(['testimonials' => $testimonials]);
}
?>