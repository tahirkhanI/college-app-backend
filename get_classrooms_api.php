<?php
header("Content-Type: application/json");

// Database configuration for XAMPP
$servername = "localhost";
$username = "root";
$password = "";
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

// Get search query if provided
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$sql = "SELECT * FROM classrooms";
if ($search) {
    $sql .= " WHERE room_number LIKE '%$search%' OR room_type LIKE '%$search%' OR block_name LIKE '%$search%'";
}

$result = $conn->query($sql);
$classrooms = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classrooms[] = [
            'id' => (int)$row['id'],
            'room_number' => $row['room_number'],
            'room_type' => $row['room_type'],
            'floor' => $row['floor'],
            'block_name' => $row['block_name']
        ];
    }
}

echo json_encode([
    'success' => true,
    'classrooms' => $classrooms
]);

$conn->close();
?>