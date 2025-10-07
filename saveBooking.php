<?php
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "resource";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Connection failed: " . $conn->connect_error]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['classroom_id']) || !isset($input['booking_date']) || !isset($input['start_time']) || !isset($input['end_time']) || !isset($input['user_id'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$classroomId = $conn->real_escape_string($input['classroom_id']);
$bookingDate = $conn->real_escape_string($input['booking_date']);
$startTime = $conn->real_escape_string($input['start_time']);
$endTime = $conn->real_escape_string($input['end_time']);
$userId = $conn->real_escape_string($input['user_id']);
$bookedBy = isset($input['booked_by']) ? $conn->real_escape_string($input['booked_by']) : "Unknown";

$startDateTime = "$bookingDate $startTime";
$endDateTime = "$bookingDate $endTime";

// Check for overlapping bookings
$overlapCheck = $conn->prepare("SELECT COUNT(*) as overlaps FROM classroom_bookings 
                               WHERE classroom_id = ? AND status = 'confirmed' 
                               AND ((? <= end_time AND ? >= start_time) 
                               OR (? <= end_time AND ? >= start_time))");
$overlapCheck->bind_param("issss", $classroomId, $startDateTime, $startDateTime, $endDateTime, $endDateTime);
$overlapCheck->execute();
$overlapResult = $overlapCheck->get_result();
$overlap = $overlapResult->fetch_assoc()['overlaps'];

if ($overlap > 0) {
    echo json_encode(["success" => false, "message" => "This time slot is already booked"]);
    $overlapCheck->close();
    $conn->close();
    exit;
}

$overlapCheck->close();

$sql = "INSERT INTO classroom_bookings (classroom_id, booking_date, start_time, end_time, user_id, status, booked_by) 
        VALUES (?, ?, ?, ?, ?, 'confirmed', ?)";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("isssis", $classroomId, $bookingDate, $startTime, $endTime, $userId, $bookedBy);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Booking saved successfully", "id" => $conn->insert_id]);
    } else {
        echo json_encode(["success" => false, "message" => "Error executing query: " . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
}

$conn->close();
?>