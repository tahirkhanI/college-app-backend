<?php
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "resource";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

$classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : 0;

$sql = "SELECT * FROM resources WHERE classroom_id = $classroom_id";
$result = $conn->query($sql);
$resources = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = [
            'id' => (int)$row['id'],
            'resource_name' => $row['resource_name'],
            'availability' => $row['availability'],
            'quantity' => (int)$row['quantity']
        ];
    }
}

echo json_encode([
    'success' => true,
    'resources' => $resources
]);

$conn->close();
?>