<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE"); // Allow all common methods
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$dbname = "resource"; // Make sure this is the correct database name you created in phpMyAdmin

// Establish database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle requests based on method
switch ($method) {
    case 'POST':
        // Handle creating a new course entry
        $data = json_decode(file_get_contents("php://input"), true);

        $faculty_name = $data['faculty_name'] ?? '';
        $course_id = $data['course_id'] ?? 0;
        $course_name = $data['course_name'] ?? '';
        $course_class_no = $data['course_class_no'] ?? '';
        $num_students_in_class = $data['num_students_in_class'] ?? 0;

        if (empty($faculty_name) || empty($course_name) || empty($course_class_no) || $course_id === 0) {
            http_response_code(400); // Bad Request
            echo json_encode(["success" => false, "message" => "Missing required fields (faculty_name, course_id, course_name, course_class_no)."]);
            break;
        }

        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO courses_management (faculty_name, course_id, course_name, course_class_no, num_students_in_class) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $faculty_name, $course_id, $course_name, $course_class_no, $num_students_in_class);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Course entry added successfully."]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(["success" => false, "message" => "Failed to add course entry: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'GET':
        // Handle fetching course entries
        $facultyId = $_GET['faculty_id'] ?? null;
        $courseId = $_GET['course_id'] ?? null;

        $sql = "SELECT * FROM courses_management";
        $conditions = [];
        $params = [];
        $types = "";

        if ($facultyId !== null) {
            $conditions[] = "faculty_id = ?";
            $params[] = $facultyId;
            $types .= "i";
        }
        if ($courseId !== null) {
            $conditions[] = "course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }

        echo json_encode(["success" => true, "data" => $courses]);
        $stmt->close();
        break;

    case 'PUT':
        // Handle updating a course entry
        $data = json_decode(file_get_contents("php://input"), true);

        $faculty_id = $data['faculty_id'] ?? null;
        $course_id = $data['course_id'] ?? null;
        $num_students_in_class = $data['num_students_in_class'] ?? null;
        $course_name = $data['course_name'] ?? null; // Allow updating course name if needed
        $course_class_no = $data['course_class_no'] ?? null; // Allow updating class no if needed

        if ($faculty_id === null || $course_id === null) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing faculty_id or course_id for update."]);
            break;
        }

        $setClauses = [];
        $params = [];
        $types = "";

        if ($num_students_in_class !== null) {
            $setClauses[] = "num_students_in_class = ?";
            $params[] = $num_students_in_class;
            $types .= "i";
        }
        if ($course_name !== null) {
            $setClauses[] = "course_name = ?";
            $params[] = $course_name;
            $types .= "s";
        }
        if ($course_class_no !== null) {
            $setClauses[] = "course_class_no = ?";
            $params[] = $course_class_no;
            $types .= "s";
        }

        if (empty($setClauses)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No fields provided for update."]);
            break;
        }

        $sql = "UPDATE courses_management SET " . implode(", ", $setClauses) . " WHERE faculty_id = ? AND course_id = ?";
        $params[] = $faculty_id;
        $params[] = $course_id;
        $types .= "ii"; // Add types for faculty_id and course_id in WHERE clause

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "Course entry updated successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "No record found or no changes made."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to update course entry: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        // Handle deleting a course entry
        $data = json_decode(file_get_contents("php://input"), true);

        $faculty_id = $data['faculty_id'] ?? null;
        $course_id = $data['course_id'] ?? null;

        if ($faculty_id === null || $course_id === null) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing faculty_id or course_id for deletion."]);
            break;
        }

        $stmt = $conn->prepare("DELETE FROM courses_management WHERE faculty_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $faculty_id, $course_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "Course entry deleted successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "No record found to delete."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to delete course entry: " . $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        // Method not allowed
        http_response_code(405); // Method Not Allowed
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}

// Close database connection
$conn->close();
?>