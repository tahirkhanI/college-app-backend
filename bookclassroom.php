
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

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
    exit;
}

// Get raw JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (
    !isset($input['classroom_id']) ||
    !isset($input['booking_date']) ||
    !isset($input['start_time']) ||
    !isset($input['end_time']) ||
    !isset($input['user_id'])
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing required fields"
    ]);
    exit;
}

$classroom_id = intval($input['classroom_id']);
$booking_date = $conn->real_escape_string($input['booking_date']);
$start_time = $conn->real_escape_string($input['start_time']);
$end_time = $conn->real_escape_string($input['end_time']);
$user_id = intval($input['user_id']);

// Validate inputs
if ($classroom_id <= 0 || $user_id <= 0 || empty($booking_date) || empty($start_time) || empty($end_time)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid input data"
    ]);
    exit;
}

// Validate date and time formats
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $booking_date)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid booking date format"
    ]);
    exit;
}
if (!preg_match("/^\d{2}:\d{2}:\d{2}$/", $start_time) || !preg_match("/^\d{2}:\d{2}:\d{2}$/", $end_time)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid time format"
    ]);
    exit;
}

// Check if classroom exists
$check_classroom = $conn->prepare("SELECT id FROM classrooms WHERE id = ?");
$check_classroom->bind_param("i", $classroom_id);
$check_classroom->execute();
if ($check_classroom->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "error" => "Classroom not found"
    ]);
    $check_classroom->close();
    $conn->close();
    exit;
}
$check_classroom->close();

// Check if user exists (optional, remove if no users table)
$check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check_user->bind_param("i", $user_id);
$check_user->execute();
if ($check_user->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "error" => "User not found"
    ]);
    $check_user->close();
    $conn->close();
    exit;
}
$check_user->close();

// Check for conflicting bookings
$conflict_query = $conn->prepare("
    SELECT id FROM classroom_bookings 
    WHERE classroom_id = ? 
    AND booking_date = ? 
    AND (
        (start_time <= ? AND end_time >= ?) 
        OR (start_time <= ? AND end_time >= ?) 
        OR (start_time >= ? AND end_time <= ?)
    )
");
$conflict_query->bind_param("isssssss", $classroom_id, $booking_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
$conflict_query->execute();
if ($conflict_query->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "error" => "Booking conflict: Classroom is already booked for this time slot"
    ]);
    $conflict_query->close();
    $conn->close();
    exit;
}
$conflict_query->close();

// Insert booking
$query = "INSERT INTO classroom_bookings (classroom_id, booking_date, start_time, end_time, user_id, created_at, updated_at) 
          VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("isssi", $classroom_id, $booking_date, $start_time, $end_time, $user_id);

if ($stmt->execute()) {
    $booking_id = $conn->insert_id;
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Booking created successfully",
        "id" => $booking_id
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Failed to create booking: " . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
