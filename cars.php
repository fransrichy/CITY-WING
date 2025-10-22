<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get_all';
    
    switch ($action) {
        case 'get_all':
            getCars();
            break;
        case 'get_by_id':
            getCarById();
            break;
        case 'get_by_type':
            getCarsByType();
            break;
        case 'get_filtered':
            getFilteredCars();
            break;
        default:
            sendError('Invalid action');
    }
}

function getCars() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM cars WHERE is_available = TRUE");
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cars as &$car) {
        $car['specs'] = json_decode($car['specs'], true);
        $car['features'] = json_decode($car['features'], true);
    }
    
    sendResponse(['cars' => $cars]);
}

function getCarById() {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        sendError('Car ID is required');
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cars WHERE id = ? AND is_available = TRUE");
    $stmt->execute([$id]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$car) {
        sendError('Car not found', 404);
    }
    
    $car['specs'] = json_decode($car['specs'], true);
    $car['features'] = json_decode($car['features'], true);
    
    sendResponse(['car' => $car]);
}

function getCarsByType() {
    $type = $_GET['type'] ?? '';
    
    if (empty($type)) {
        sendError('Car type is required');
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cars WHERE type = ? AND is_available = TRUE");
    $stmt->execute([$type]);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cars as &$car) {
        $car['specs'] = json_decode($car['specs'], true);
        $car['features'] = json_decode($car['features'], true);
    }
    
    sendResponse(['cars' => $cars]);
}

function getFilteredCars() {
    $type = $_GET['type'] ?? '';
    $priceRange = $_GET['price_range'] ?? '';
    $passengerCapacity = $_GET['passenger_capacity'] ?? '';
    $transmission = $_GET['transmission'] ?? '';
    
    $db = getDB();
    $sql = "SELECT * FROM cars WHERE is_available = TRUE";
    $params = [];
    
    if (!empty($type) && $type !== 'all') {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    if (!empty($priceRange) && $priceRange !== 'all') {
        switch ($priceRange) {
            case 'budget':
                $sql .= " AND price BETWEEN 50 AND 80";
                break;
            case 'mid':
                $sql .= " AND price BETWEEN 81 AND 120";
                break;
            case 'premium':
                $sql .= " AND price >= 121";
                break;
        }
    }
    
    if (!empty($transmission) && $transmission !== 'all') {
        $sql .= " AND JSON_EXTRACT(specs, '$.transmission') = ?";
        $params[] = $transmission;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter by passenger capacity if needed
    if (!empty($passengerCapacity) && $passengerCapacity !== 'all') {
        $filteredCars = [];
        foreach ($cars as $car) {
            $specs = json_decode($car['specs'], true);
            $passengers = $specs['passengers'] ?? 0;
            
            switch ($passengerCapacity) {
                case '1-4':
                    if ($passengers <= 4) $filteredCars[] = $car;
                    break;
                case '5-7':
                    if ($passengers >= 5 && $passengers <= 7) $filteredCars[] = $car;
                    break;
                case '8+':
                    if ($passengers >= 8) $filteredCars[] = $car;
                    break;
            }
        }
        $cars = $filteredCars;
    }
    
    foreach ($cars as &$car) {
        $car['specs'] = json_decode($car['specs'], true);
        $car['features'] = json_decode($car['features'], true);
    }
    
    sendResponse(['cars' => $cars]);
}
?>