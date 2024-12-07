<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$database = "id22126747_myproject";
$port = 3307;
date_default_timezone_set('Asia/Kolkata');

// Create connection
$conn = new mysqli($servername, $username, $password, $database,$port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read JSON input from the POST request
$data = json_decode(file_get_contents('php://input'), true);

$score = 0;
$examTitle = $data['exam_title'] ?? '';
$username = $data['username'] ?? '';
$answers = $data['answers'] ?? [];
$datetime = date('Y-m-d H:i:s'); // Current datetime

// Check if data is received
if (!empty($answers)) {
    // Loop through submitted answers
    foreach ($answers as $key => $value) {
        // Extract question ID and submitted option ID
        $question_id = str_replace('answer_', '', $key);
        $submitted_option_value = $value;

        // Fetch correct option ID from database
        $fetch_correct_option_sql = "SELECT correct_answer FROM questions WHERE id='$question_id'";
        $result = $conn->query($fetch_correct_option_sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $correct_option_value = $row['correct_answer'];

            // Check if submitted option is correct
            if ($submitted_option_value == $correct_option_value) {
                $score++; // Increment score for correct answer
            }
        }
    }

    // Insert exam results into database
    $insert_results_sql = "INSERT INTO exam_results (exam_title, username, score, exam_datetime) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_results_sql);

    if ($stmt) {
        // Bind parameters and execute the statement
        $stmt->bind_param("ssis", $examTitle, $username, $score, $datetime);
        $stmt->execute();
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
        } else {
            echo json_encode(["success" => false, "error" => "Error preparing SQL statement for updating student_id: " . $conn->error]);
            exit();
        }

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Error preparing SQL statement for inserting results: " . $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "error" => "No answers received."]);
}

$conn->close();
?>
