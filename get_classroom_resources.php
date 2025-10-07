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

// Check if classroom_id is provided
if (!isset($_GET['classroom_id']) || !is_numeric($_GET['classroom_id'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing or invalid classroom_id"
    ]);
    exit;
}

$classroom_id = intval($_GET['classroom_id']);

// Fetch resources
$query = "SELECT id, classroom_id, resource_name, availability, quantity, created_at 
          FROM resources 
          WHERE classroom_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$result = $stmt->get_result();

$resources = [];
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

echo json_encode([
    "success" => true,
    "resources" => $resources
]);

$stmt->close();
$conn->close();
?>
