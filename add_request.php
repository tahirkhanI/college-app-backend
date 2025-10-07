<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$username = "root";
$password = "";
$dbname = "resource";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "DB connection failed"]));
}

$data = json_decode(file_get_contents("php://input"), true);

// Extract data from POST
$room_id = (int)$data["room_id"];
$requested_by_role = $conn->real_escape_string($data["requested_by_role"]);
$requested_by_id = (int)$data["requested_by_id"];
$request_type = $conn->real_escape_string($data["request_type"]);
$request_description = $conn->real_escape_string($data["request_description"]);

// Insert query
$sql = "INSERT INTO resource_requests (
    room_id, requested_by_role, requested_by_id, request_type, request_description
) VALUES (
    $room_id, '$requested_by_role', $requested_by_id, '$request_type', '$request_description'
)";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Request submitted successfully"]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$conn->close();
?>
