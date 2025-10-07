<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for development only. Comment out in production!
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer autoloader (make sure vendor folder exists)
require __DIR__ . '/vendor/autoload.php';

// Database connection — make sure $pdo is set (PDO version of dbconns.php)
include 'dbconns.php';

header('Content-Type: application/json');

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

try {
    // Get and decode JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["email"]) || empty(trim($data["email"]))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email is required"]);
        exit;
    }

    // Sanitize & validate email
    $email = filter_var(trim($data["email"]), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Valid email is required"]);
        exit;
    }

    // ==== Check if email exists in login table ====
    try {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM login WHERE email = :email");
        $stmtCheck->execute([':email' => $email]);
        $exists = $stmtCheck->fetchColumn();

        if (!$exists) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "No matching account exists for this email."
            ]);
            exit;
        }
    } catch (PDOException $exCheck) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Database error during email check: " . $exCheck->getMessage()
        ]);
        exit;
    }

    // Generate OTP securely
    $otp = random_int(100000, 999999);

    // Store OTP in password_resets table
    try {
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, created_at)
            VALUES (:email, :otp_insert, NOW())
            ON DUPLICATE KEY UPDATE otp = :otp_update, created_at = NOW()");
        $stmt->execute([
            ':email' => $email,
            ':otp_insert' => $otp,
            ':otp_update' => $otp,
        ]);
    } catch (PDOException $ex) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Database error while storing OTP: " . $ex->getMessage()
        ]);
        exit;
    }

    // Send OTP email using PHPMailer
    try {
        $mail = new PHPMailer(true);

        // SMTP config
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kdhanalakshmi2005@gmail.com'; // Your Gmail
        $mail->Password = 'stth lhvp egwr ycfv';          // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('kdhanalakshmi2005@gmail.com', 'EduConnect');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "<p>Your OTP for password reset is: <strong>$otp</strong></p>
                          <p>If you did not request this, please ignore this email.</p>";

        $mail->send();

        // Success response (never send OTP in API response)
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "OTP sent to your email."
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Mail error: " . $mail->ErrorInfo
        ]);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}

// No closing PHP tag to prevent accidental whitespace output
