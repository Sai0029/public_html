<?php
$selectCourse = filter_input(INPUT_POST, "selectCourse", FILTER_SANITIZE_STRING);
$examTimeLimit = filter_input(INPUT_POST, "examTimeLimit", FILTER_VALIDATE_INT);
$questionLimit = filter_input(INPUT_POST, "questionLimit", FILTER_VALIDATE_INT);
$examTitle = filter_input(INPUT_POST, "examTitle", FILTER_SANITIZE_STRING);
$difficulty = filter_input(INPUT_POST, "difficulty", FILTER_SANITIZE_STRING);
$startDateTime = filter_input(INPUT_POST, "start_date_time", FILTER_SANITIZE_STRING);
$endDateTime = filter_input(INPUT_POST, "end_date_time", FILTER_SANITIZE_STRING);

$servername = "localhost"; // Replace with your server name
    $dbname = "id22126747_myproject"; // Replace with your database name
    $username = "root"; // Default MySQL username for XAMPP
    $password = ""; // Default MySQL password for XAMPP is empty
    $port = 3307; // Your MySQL port number

$conn = mysqli_connect($servername, $username, $password, $dbname,$port);

if (mysqli_connect_errno()) {
    die("Connection error: " . mysqli_connect_error());
}

$sql = "INSERT INTO exams (course_name, time_limit, question_limit, exam_title, difficulty, start_date_time, end_date_time)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_stmt_init($conn);

if (!mysqli_stmt_prepare($stmt, $sql)) {
    die(mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "siissss", $selectCourse, $examTimeLimit, $questionLimit, $examTitle, $difficulty, $startDateTime, $endDateTime);

if (mysqli_stmt_execute($stmt)) {
    echo "Record saved successfully.";
} else {
    echo "Error saving record: " . mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
