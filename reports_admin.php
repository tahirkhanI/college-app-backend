<?php
header("Content-Type: application/json");

// Database connection
$servername = "localhost";
$username = "root"; // change if needed
$password = "";     // change if needed
$dbname = "resource"; // change if needed

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['status'])) {
    $report_id = intval($_POST['report_id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE report_id = ?");
    $stmt->bind_param("si", $status, $report_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Status updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating status"]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Fetch all reports
$sql = "SELECT r.report_id, l.name AS reporter_name, r.classroom_number, 
               r.problem_description, r.status, r.reported_at
        FROM reports r
        JOIN login l ON r.reporter_id = l.id
        ORDER BY r.reported_at DESC";

$result = $conn->query($sql);

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

echo json_encode(["success" => true, "reports" => $reports]);

$conn->close();
?>
