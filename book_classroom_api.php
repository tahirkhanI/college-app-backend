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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$classroom_id = (int)$data['classroom_id'];
$booking_date = $conn->real_escape_string($data['booking_date']);
$start_time = $conn->real_escape_string($data['start_time']);
$end_time = $conn->real_escape_string($data['end_time']);
$user_id = (int)$data['user_id'];

// Check for overlapping bookings
$sql = "SELECT * FROM classroom_bookings 
        WHERE classroom_id = $classroom_id 
        AND booking_date = '$booking_date'
        AND (
            (start_time <= '$start_time' AND end_time > '$start_time') OR
            (start_time < '$end_time' AND end_time >= '$end_time') OR
            (start_time >= '$start_time' AND end_time <= '$end_time')
        )";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "This time slot is already booked"
    ]);
    exit;
}

// Insert new booking
$sql = "INSERT INTO classroom_bookings (classroom_id, booking_date, start_time, end_time, user_id, status, created_at)
        VALUES ($classroom_id, '$booking_date', '$start_time', '$end_time', $user_id, 'confirmed', NOW())";

if ($conn->query($sql) === TRUE) {
    echo json_encode([
        "success" => true,
        "message" => "Booking successful"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error creating booking: " . $conn->error
    ]);
}

$conn->close();
?>