
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

// Check if user_id is provided
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing or invalid user_id"
    ]);
    exit;
}

$user_id = intval($_GET['user_id']);

// Fetch bookings
$query = "SELECT id, classroom_id, booking_date, start_time, end_time, user_id, status, created_at, updated_at 
          FROM classroom_bookings 
          WHERE user_id = ? AND status = 'Confirmed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode([
    "success" => true,
    "bookings" => $bookings
]);

$stmt->close();
$conn->close();
?>
