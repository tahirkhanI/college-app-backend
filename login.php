<?php
header("Content-Type: application/json");
$host = "localhost";
$db = "resource";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection error"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(["status" => "error", "message" => "Missing fields"]);
        exit();
    }

    // Fetch full user info
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $email, $hashed, $role);
        $stmt->fetch();
        if (password_verify($password, $hashed)) {
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "role" => $role,
                "user" => [
                    "id" => $id,
                    "name" => $name,
                    "email" => $email
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
$conn->close();
?>
