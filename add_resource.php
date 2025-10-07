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
if (!isset($input['classroom_id']) || !isset($input['resource_name']) || !isset($input['quantity']) || !isset($input['availability'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing required fields"
    ]);
    exit;
}

$classroom_id = intval($input['classroom_id']);
$resource_name = $input['resource_name'];
$quantity = intval($input['quantity']);
$availability = $input['availability'];

// Validate inputs
if ($classroom_id <= 0 || empty($resource_name) || $quantity <= 0 || !in_array($availability, ['Available', 'Unavailable'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid input data"
    ]);
    exit;
}

// Add resource
$query = "INSERT INTO resources (classroom_id, resource_name, availability, quantity, created_at) 
          VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("issi", $classroom_id, $resource_name, $availability, $quantity);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Resource added successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to add resource"
    ]);
}

$stmt->close();
$conn->close();
?>
