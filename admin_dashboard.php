<?php
header("Content-Type: application/json");

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "resource";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

// Get email from URL (e.g., ?email=adm@test.com)
$email = $_GET['email'] ?? '';

// Optional: Check if email exists
$user_check = $conn->query("SELECT name, role FROM login WHERE email = '$email'");
if ($user_check->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit();
}

$user_data = $user_check->fetch_assoc();
$username = $user_data['name'];
$role = $user_data['role'];

// Get statistics
$total_rooms = $conn->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
$total_faculty = $conn->query("SELECT COUNT(*) as total FROM login WHERE role = 'faculty'")->fetch_assoc()['total'];
$total_students = $conn->query("SELECT COUNT(*) as total FROM login WHERE role = 'student'")->fetch_assoc()['total'];
$total_requests = $conn->query("SELECT COUNT(*) as total FROM resource_requests")->fetch_assoc()['total'];

// Return JSON response
$response = [
    "status" => "success",
    "user" => [
        "name" => $username,
        "email" => $email,
        "role" => $role
    ],
    "total_rooms" => $total_rooms,
    "total_faculty" => $total_faculty,
    "total_students" => $total_students,
    "total_requests" => $total_requests
];

echo json_encode($response);
?>
