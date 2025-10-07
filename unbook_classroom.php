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
if (!isset($input['booking_id']) || !is_numeric($input['booking_id'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing or invalid booking_id"
    ]);
    exit;
}

$booking_id = intval($input['booking_id']);

// Update booking status to Cancelled
$query = "UPDATE classroom_bookings SET status = 'Cancelled', updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Booking cancelled successfully"
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "Booking not found"
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to cancel booking"
    ]);
}

$stmt->close();
$conn->close();
?>