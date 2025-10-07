<?php
header('Content-Type: application/json');
// Enable error reporting for debugging - disable in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dbconns.php'; // Make sure this sets up a PDO instance as $pdo

// Accept only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit;
}

// Read POST data (JSON)
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

// Extract and sanitize input fields
$email = isset($data["email"]) ? trim($data["email"]) : "";
$otp = isset($data["otp"]) ? trim($data["otp"]) : "";
$new_password = isset($data["new_password"]) ? $data["new_password"] : "";

// Validate all fields are provided
if (empty($email) || empty($otp) || empty($new_password)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required."
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Valid email is required."
    ]);
    exit;
}

try {
    // 1. Fetch OTP info from password_resets table
    $stmt = $pdo->prepare("SELECT otp, created_at FROM password_resets WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if OTP exists and matches
    if (!$row || $otp !== $row['otp']) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Invalid OTP or email."
        ]);
        exit;
    }

    // 2. Verify OTP expiration (valid for 15 minutes = 900 seconds)
    $otpCreatedTime = strtotime($row['created_at']);
    if ((time() - $otpCreatedTime) > 900) {
        // Delete expired OTP record
        $pdo->prepare("DELETE FROM password_resets WHERE email = :email")->execute([':email' => $email]);

        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "OTP has expired. Please request a new one."
        ]);
        exit;
    }

    // 3. Securely hash the new password
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

    // 4. Update password in login table
    $updateStmt = $pdo->prepare("UPDATE login SET password = :password WHERE email = :email");
    $updateStmt->execute([
        ':password' => $hashedPassword,
        ':email' => $email
    ]);

    if ($updateStmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "No user found with this email; password not updated."
        ]);
        exit;
    }

    // 5. Delete OTP after successful reset to prevent reuse
    $pdo->prepare("DELETE FROM password_resets WHERE email = :email")->execute([':email' => $email]);

    // 6. Send success JSON response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Password has been reset successfully."
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
}

// No closing PHP tag (to prevent accidental output)
