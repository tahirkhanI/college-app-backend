<?php
header("Content-Type: application/json");

// Database connection
$conn = new mysqli("localhost", "root", "", "resource");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Get POST JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data["user_id"], $data["classroom_id"], $data["issue_description"])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Escape values
$user_id = (int)$data["user_id"];
$classroom_id = (int)$data["classroom_id"];
$issue_description = $conn->real_escape_string($data["issue_description"]);
$status = "pending";

// Insert into table
$sql = "INSERT INTO complaints (user_id, classroom_id, issue_description, status) 
        VALUES ($user_id, $classroom_id, '$issue_description', '$status')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "id" => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(["error" => $conn->error]);
}

$conn->close();
?>
