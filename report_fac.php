<?php
header("Content-Type: application/json");

// Database configuration for XAMPP
$servername = "localhost";
$username   = "root";
$password   = "";   // XAMPP default has no password
$dbname     = "resource";

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (
        !isset($input['reporter_id']) ||
        !isset($input['classroom_number']) ||
        !isset($input['problem_description'])
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Missing required fields"
        ]);
        exit;
    }

    $reporter_id         = (int)$input['reporter_id'];
    $classroom_number    = $conn->real_escape_string($input['classroom_number']);
    $problem_description = $conn->real_escape_string($input['problem_description']);
    $status              = "Pending";
    $reported_at         = date("Y-m-d H:i:s");

    // ✅ Check if reporter exists in login table
    $checkUser = $conn->prepare("SELECT id FROM login WHERE id = ?");
    $checkUser->bind_param("i", $reporter_id);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows === 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid reporter_id: user does not exist"
        ]);
        exit;
    }

    // ✅ Insert into reports table
    $stmt = $conn->prepare("INSERT INTO reports 
        (reporter_id, classroom_number, problem_description, status, reported_at) 
        VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issss", $reporter_id, $classroom_number, $problem_description, $status, $reported_at);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Report submitted successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Failed to submit report: " . $stmt->error
        ]);
    }

    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
}

$conn->close();
?>
