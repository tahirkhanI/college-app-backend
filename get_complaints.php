<?php
header("Content-Type: application/json");

// Database connection
$conn = new mysqli("localhost", "root", "", "resource");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Fetch complaints and classroom number
$sql = "
    SELECT 
        c.id,
        c.user_id,
        c.classroom_id,
        cl.room_number,
        c.issue_description,
        c.status,
        c.created_at
    FROM complaints c
    INNER JOIN classrooms cl ON c.classroom_id = cl.id
    ORDER BY c.created_at DESC
";

$result = $conn->query($sql);

$complaints = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $complaints[] = [
            "id" => (int)$row['id'],
            "user_id" => (int)$row['user_id'],
            "classroom_id" => (int)$row['classroom_id'],
            "room_number" => $row['room_number'],
            "issue_description" => $row['issue_description'],
            "status" => $row['status'],
            "created_at" => $row['created_at']
        ];
    }
}

echo json_encode($complaints);

$conn->close();
?>
