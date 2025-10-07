<?php
header("Content-Type: application/json");

// Database connection config
$conn = new mysqli("localhost", "root", "", "resource");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([]);
    exit();
}

// Fetch users (adjust fields as per your table)
$result = $conn->query("SELECT id, name, email, role FROM login");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        "id" => (int)$row["id"],
        "name" => $row["name"],
        "email" => $row["email"],
        "role" => $row["role"]
    ];
}

echo json_encode($users);

$conn->close();
