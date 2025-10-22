<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get_all';
    
    switch ($action) {
        case 'get_all':
            getServices();
            break;
        case 'get_by_id':
            getServiceById();
            break;
        case 'get_by_category':
            getServicesByCategory();
            break;
        default:
            sendError('Invalid action');
    }
}

function getServices() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM services WHERE is_active = TRUE");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode JSON fields
    foreach ($services as &$service) {
        $service['features'] = json_decode($service['features'], true);
    }
    
    sendResponse(['services' => $services]);
}

function getServiceById() {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        sendError('Service ID is required');
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        sendError('Service not found', 404);
    }
    
    $service['features'] = json_decode($service['features'], true);
    
    sendResponse(['service' => $service]);
}

function getServicesByCategory() {
    $category = $_GET['category'] ?? '';
    
    if (empty($category)) {
        sendError('Category is required');
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM services WHERE category = ? AND is_active = TRUE");
    $stmt->execute([$category]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($services as &$service) {
        $service['features'] = json_decode($service['features'], true);
    }
    
    sendResponse(['services' => $services]);
}
?>