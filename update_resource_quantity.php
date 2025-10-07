<?php
header("Content-Type: application/json");

// Database configuration for XAMPP
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP default has no password
$dbname = "resource";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['resource_id']) || !isset($input['quantity']) || !is_numeric($input['resource_id']) || !is_numeric($input['quantity'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing or invalid resource_id or quantity"
    ]);
    exit;
}

$resource_id = intval($input['resource_id']);
$quantity = intval($input['quantity']);

// Validate quantity
if ($quantity <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Quantity must be greater than 0"
    ]);
    exit;
}

// Update resource quantity
$query = "UPDATE resources SET quantity = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $quantity, $resource_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Quantity updated successfully"
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "Resource not found"
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to update quantity"
    ]);
}

$stmt->close();
$conn->close();
?>
