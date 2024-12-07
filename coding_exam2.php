<?php
    session_start();

    if (!isset($_SESSION["username"])) {
        header("Location: login.php");
        exit();
    }

    $username = $_SESSION["username"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }

        .red-text {
            color: red;
        }

        .question-container {
            margin-bottom: 30px;
        }

        .timer-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .timer {
            position: fixed;
            display: none;
            top: 10px;
            right: 10px;
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .options-list {
            list-style-type: none; /* Remove bullets */
        }

        .palette-item {
            display: inline-block;
            width: 30px;
            height: 30px;
            margin: 5px;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            cursor: pointer;
        }

        .summary-box {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">All The Best! <?php echo $username; ?></h1>
    <?php
    if (isset($_GET['exam_title'])) {
        $examTitle = $_GET['exam_title'];
        echo '<input type="hidden" id="examTitleInput" name="exam_title" value="' . $examTitle . '">';
    }
    ?>
    <div class="text-center mb-4">
        <button id="markAttendanceBtn" class="btn btn-primary">Mark Attendance</button>
    </div>
    <div class="question-container">
        <form id="examForm" action="submit_action.php" method="POST" style="display: none;">
        <input type="hidden" name="username" value="<?php echo $username; ?>">
        <input type="hidden" name="exam_title" value="<?php echo $examTitle; ?>">
            <?php
            $servername = "localhost"; // Replace with your server name
            $database = "id22126747_myproject"; // Replace with your database name
            $username = "root"; // Default MySQL username for XAMPP
            $password = "";

            $conn = new mysqli($servername, $username, $password, $database);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            if (isset($_GET['exam_title']) && isset($_GET['difficulty'])) {
                $examTitle = $_GET['exam_title'];
                $examDifficulty = $_GET['difficulty'];

                $fetch_time_limit_sql = "SELECT time_limit FROM exams WHERE exam_title='$examTitle' AND difficulty='$examDifficulty'";
                $time_limit_result = $conn->query($fetch_time_limit_sql);

                if ($time_limit_result && $time_limit_result->num_rows > 0) {
                    $row = $time_limit_result->fetch_assoc();
                    $timeLimitMinutes = $row['time_limit'];
                } else {
                    $timeLimitMinutes = 30;
                }

                $fetch_question_limit_sql = "SELECT question_limit FROM exams WHERE exam_title='$examTitle' AND difficulty='$examDifficulty'";
                $question_limit_result = $conn->query($fetch_question_limit_sql);

                if ($question_limit_result && $question_limit_result->num_rows > 0) {
                    $row = $question_limit_result->fetch_assoc();
                    $questionLimit = $row['question_limit'];
                } else {
                    $questionLimit = 5;
                }

                $fetch_questions_sql = "SELECT * FROM questions WHERE topic='$examTitle' AND difficulty='$examDifficulty' ORDER BY RAND() LIMIT $questionLimit";
                $result = $conn->query($fetch_questions_sql);

                $questionNumber = 1;

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='mb-3' id='question$questionNumber'>";
                        echo "<p><strong>Question $questionNumber:</strong> " . $row["question_text"] . "</p>";
                        $question_id = $row["id"];
                        $fetch_options_sql = "SELECT * FROM options WHERE question_id='$question_id'";
                        $options_result = $conn->query($fetch_options_sql);

                        echo "<ul class='options-list'>";
                        while ($option_row = $options_result->fetch_assoc()) {
                            echo "<li><input type='radio' name='answer_$question_id' value='{$option_row['id']}' onclick='updateQuestionStatus($questionNumber)' required>{$option_row['option_text']}</li>";
                        }
                        echo "</ul>";
                        echo "<button type='button' onclick='markForReview($questionNumber)'>Mark for Review</button>";
                        echo "<button type='button' onclick='clearResponse($questionNumber)'>Clear Response</button>";
                        echo "</div>";

                        $questionNumber++;
                    }
                    echo "<button type='submit' id='submitExam' style='display:none;'>Submit Exam</button>";
                } else {
                    echo "No questions available for the selected topic and difficulty.";
                }
            }
            ?>
        </form>
    </div>
    <div id="palette" class="mb-4"></div>
    <div class="summary-box">
        <p>Answered: <span id="answeredCount">0</span></p>
        <p>Unanswered: <span id="unansweredCount">0</span></p>
        <p>Yet to Visit: <span id="yetToVisitCount">0</span></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function(){
        $('#markAttendanceBtn').click(function(){
            captureImage();
        });

        generatePalette();
        updateSummary();
    });

    let totalQuestions = <?php echo $questionNumber - 1; ?>;
    let answeredCount = 0;
    let currentQuestion = 1;

    function markForReview(questionNumber) {
        const paletteItem = document.getElementById(`palette-item-${questionNumber}`);
        paletteItem.classList.remove('attempted', 'correct');
        paletteItem.classList.toggle('review');
        updatePalette();
    }

    function clearResponse(questionNumber) {
        const answer = document.querySelector(`input[name="answer_${questionNumber}"]:checked`);
        if (answer) {
            answer.checked = false;
            answeredCount--;
        }
        const paletteItem = document.getElementById(`palette-item-${questionNumber}`);
        paletteItem.classList.remove('correct', 'attempted', 'review');
        updatePalette();
        updateSummary();
    }

    function updateSummary() {
        document.getElementById('answeredCount').innerText = answeredCount;
        document.getElementById('unansweredCount').innerText = totalQuestions - answeredCount;
        document.getElementById('yetToVisitCount').innerText = Math.max(totalQuestions - answeredCount - currentQuestion + 1, 0);
    }

    function updatePalette() {
        for (let i = 1; i <= totalQuestions; i++) {
            const paletteItem = document.getElementById(`palette-item-${i}`);
            if (paletteItem) {
                if (paletteItem.classList.contains('review')) {
                    paletteItem.style.backgroundColor = 'violet';
                } else if (paletteItem.classList.contains('correct')) {
                    paletteItem.style.backgroundColor = 'green';
                } else if (paletteItem.classList.contains('attempted')) {
                    paletteItem.style.backgroundColor = 'orange';
                } else {
                    paletteItem.style.backgroundColor = '#ccc';
                }
            }
        }
    }

    function generatePalette() {
        const palette = document.getElementById('palette');
        for (let i = 1; i <= totalQuestions; i++) {
            const paletteItem = document.createElement('div');
            paletteItem.classList.add('palette-item');
            paletteItem.id = `palette-item-${i}`;
            paletteItem.innerText = i;
            paletteItem.setAttribute('onclick', `goToQuestion(${i})`);
            palette.appendChild(paletteItem);
        }
    }

    function goToQuestion(questionNumber) {
        for (let i = 1; i <= totalQuestions; i++) {
            document.getElementById(`question${i}`).style.display = 'none';
        }
        document.getElementById(`question${questionNumber}`).style.display = 'block';
        currentQuestion = questionNumber;
        updateSummary();
    }

    function updateQuestionStatus(questionNumber) {
        const paletteItem = document.getElementById(`palette-item-${questionNumber}`);
        paletteItem.classList.remove('review');
        if (document.querySelector(`input[name="answer_${questionNumber}"]:checked`)) {
            paletteItem.classList.add('correct');
            answeredCount++;
        } else {
            paletteItem.classList.remove('correct');
        }
        updatePalette();
        updateSummary();
    }

    function captureImage() {
        Swal.fire({
            title: 'Marking Attendance...',
            html: 'Please wait...',
            timer: 3000,
            onBeforeOpen: () => {
                Swal.showLoading();
            },
            onClose: () => {
                $('#examForm').show();
                $('.timer').show();
                startTimer(<?php echo $timeLimitMinutes; ?>);
            }
        });
    }

    function startTimer(duration) {
        let timer = duration * 60, minutes, seconds;
        const display = document.querySelector('.timer');
        setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                document.getElementById("submitExam").click();
            }
        }, 1000);
    }
</script>
<div class="timer">00:00</div>
</body>
</html>
