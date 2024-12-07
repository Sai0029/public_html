<?php
session_start(); // Start the PHP session

// Check if username is set in session
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Database connection
include 'db_connection.php';

// Get the JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$username = $_SESSION['username'];
$examTitle = $data['examTitle'];
$score = $data['score'];
$datetime = date('Y-m-d H:i:s');

$insert_results_sql = "INSERT INTO exam_results (exam_title, username, score, exam_datetime) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insert_results_sql);

if ($stmt) {
    // Bind parameters and execute the statement
    $stmt->bind_param("ssis", $examTitle, $username, $score, $datetime);
    if ($stmt->execute()) {
        // Close statement
        $stmt->close();

        // Update student_id in exam_results based on username
        $update_student_id_sql = "UPDATE exam_results er
                                JOIN student_users su ON er.username = su.username
                                SET er.student_id = su.student_id
                                WHERE er.username = ?";
        $stmt_update = $conn->prepare($update_student_id_sql);
        
        if ($stmt_update) {
            $stmt_update->bind_param("s", $username);
            $stmt_update->execute();
            $stmt_update->close();
            echo json_encode(['success' => true, 'message' => 'Exam results saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error preparing SQL statement for updating student_id: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error executing SQL statement for inserting exam results: ' . $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing SQL statement for inserting exam results: ' . $conn->error]);
}

$conn->close();
?>
