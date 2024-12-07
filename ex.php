<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION["username"];

if (!isset($_SESSION["served_questions"])) {
    $_SESSION["served_questions"] = [];
}
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
            list-style-type: none;
        }

        .palette-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .palette-item {
            width: 30px;
            height: 30px;
            background-color: lightgray;
            margin: 2px;
            text-align: center;
            line-height: 30px;
            cursor: pointer;
        }

        .palette-item.correct {
            background-color: green;
            color: white;
        }

        .palette-item.incorrect {
            background-color: red;
            color: white;
        }

        .summary-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .question-block {
            display: none;
        }

        .question-block.active {
            display: block;
        }

        .question-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .camera-feed {
            width: 100%;
            height: auto;
            border: 10px solid transparent;
            border-radius: 10px;
        }

        .main-container {
            display: flex;
            justify-content: space-between;
        }

        .questions-container {
            width: 70%;
        }

        .camera-container {
            width: 25%;
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
    <div class="palette-container" id="questionPalette"></div>
    <div class="summary-container">
        <div>Answered: <span id="answeredCount">0</span></div>
        <div style="margin-left: 20px;">Unanswered: <span id="unansweredCount">0</span></div>
    </div>
    <div class="main-container">
        <div class="questions-container">
            <div class="question-container">
                <form id="examForm" action="submit_action.php" method="POST" style="display: none;">
                    <input type="hidden" name="username" value="<?php echo $username; ?>">
                    <input type="hidden" name="exam_title" value="<?php echo $examTitle; ?>">
                    <?php
                    $servername = "localhost";
                    $database = "id22126747_myproject";
                    $db_username = "root";
                    $db_password = "";

                    $conn = new mysqli($servername, $db_username, $db_password, $database);
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

                        $servedQuestions = implode(',', $_SESSION["served_questions"]);
                        if (empty($servedQuestions)) {
                            $servedQuestions = '0';  // To handle empty case initially
                        }

                        $fetch_questions_sql = "SELECT * FROM questions WHERE topic='$examTitle' AND difficulty='$examDifficulty' AND id NOT IN ($servedQuestions) ORDER BY RAND() LIMIT $questionLimit";
                        $result = $conn->query($fetch_questions_sql);

                        $questionNumber = 1;

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<div id='question$questionNumber' class='question-block'>";
                                echo "<p><strong>Question $questionNumber:</strong> " . $row["question_text"] . "</p>";
                                $question_id = $row["id"];
                                $_SESSION["served_questions"][] = $question_id; // Add question to served questions
                                $fetch_options_sql = "SELECT * FROM options WHERE question_id='$question_id'";
                                $options_result = $conn->query($fetch_options_sql);

                                echo "<ul class='options-list'>";
                                while ($option_row = $options_result->fetch_assoc()) {
                                    echo "<li><input type='radio' name='answer_$question_id' value='{$option_row['id']}' required onchange='updateQuestionStatus($questionNumber)'>{$option_row['option_text']}</li>";
                                }
                                echo "</ul>";
                                echo "<button type='button' class='btn btn-warning' onclick='clearResponse($questionNumber)'>Clear Response</button>";
                                if ($questionNumber == $questionLimit) {
                                    echo "<button type='submit' class='btn btn-success'>Submit Exam</button>";
                                }
                                echo "</div>";

                                $questionNumber++;
                            }
                        } else {
                            echo "No questions available for the selected topic and difficulty.";
                        }
                    }
                    ?>
                </form>
            </div>
            <div class="text-center question-footer">
                <button id="prevBtn" class="btn btn-secondary" onclick="showPreviousQuestion()">Previous</button>
                <button id="nextBtn" class="btn btn-secondary" onclick="showNextQuestion()">Next</button>
            </div>
        </div>
        <div class="camera-container">
            <video id="cameraFeed" class="camera-feed" autoplay></video>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
    var currentQuestion = 1;
    var totalQuestions = <?php echo $questionNumber - 1; ?>;
    var keyPressed = false;
    var tabSwitchCount = 0;
    var faceAlignmentErrorCount = 0;

    $(document).ready(function() {
        showQuestion(currentQuestion);
        let paletteContainer = $('#questionPalette');
        for (let i = 1; i <= totalQuestions; i++) {
            paletteContainer.append(`<div class="palette-item" id="paletteItem${i}" onclick="scrollToQuestion(${i})">${i}</div>`);
        }
        updateSummary();
        $('#markAttendanceBtn').click(function() {
            captureImage();
        });
    });

    function captureImage() {
        const video = document.getElementById('cameraFeed');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                setTimeout(() => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const imageData = canvas.toDataURL('image/jpeg');
                    stream.getTracks().forEach(track => track.stop());
                    markAttendance(imageData);
                }, 1000);
            })
            .catch(error => {
                console.error('Error accessing camera:', error);
                Swal.fire({
                    title: "Error",
                    text: "Failed to access camera. Please try again.",
                    icon: "error",
                    confirmButtonText: "OK"
                });
            });
    }

    function markAttendance(imageData) {
        $.ajax({
            type: "POST",
            url: "https://192.168.29.183:5000/mark-attendance",
            contentType: "application/json",
            data: JSON.stringify({ "username": "<?php echo $username; ?>", "image": imageData }),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: "Attendance Marked Successfully",
                        text: response.message,
                        icon: "success",
                        confirmButtonText: "OK"
                    });
                    $('#examForm').show();
                    $('#markAttendanceBtn').hide();
                    startTimer(<?php echo $timeLimitMinutes; ?>);
                    enterFullScreen();
                    startCameraFeed();
                } else {
                    Swal.fire({
                        title: "Attendance Failed",
                        text: "No face detected or authentication failed. Please try again.",
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                Swal.fire({
                    title: "Error",
                    text: "An error occurred while marking attendance. Please try again.",
                    icon: "error",
                    confirmButtonText: "OK"
                });
            }
        });
    }

    function startTimer(duration) {
        var timer = duration * 60;
        var timerElement = document.createElement('div');
        timerElement.classList.add('timer');
        document.body.appendChild(timerElement);
        timerElement.style.display = 'block';
        var interval = setInterval(function() {
            var minutes = Math.floor(timer / 60);
            var seconds = timer % 60;
            timerElement.textContent = `Time left: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            if (--timer < 0) {
                clearInterval(interval);
                timerElement.style.display = 'none';
                $('#examForm').submit();
            }
        }, 1000);
    }

    function startCameraFeed() {
        const cameraFeed = document.getElementById('cameraFeed');
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                cameraFeed.srcObject = stream;
                setTimeout(captureAndSendFace, 10000); // Start capturing face after 10 seconds
            })
            .catch(err => {
                console.error('Error accessing camera:', err);
            });
    }

    function captureAndSendFace() {
        const cameraFeed = document.getElementById('cameraFeed');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = cameraFeed.videoWidth;
        canvas.height = cameraFeed.videoHeight;
        ctx.drawImage(cameraFeed, 0, 0, canvas.width, canvas.height);
        const imageData = canvas.toDataURL('image/jpeg');

        sendFaceToServer(imageData, "<?php echo $username; ?>");
        setTimeout(captureAndSendFace, 10000); // Repeat every 10 seconds
    }

    function sendFaceToServer(imageData, username) {
        fetch("https://192.168.29.183:5000/mark-attendance", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ "username": username, "image": imageData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('cameraFeed').style.borderColor = 'green';
                faceAlignmentErrorCount = 0; // Reset error count on success
            } else {
                document.getElementById('cameraFeed').style.borderColor = 'red';
                faceAlignmentErrorCount++;
                Swal.fire({
                    icon: 'error',
                    title: 'User Mismatch',
                    text: 'Please return back to exam.'
                });
                if (faceAlignmentErrorCount >= 3) {
                    window.location.href = "student_dashboard.php";
                }
            }
        })
        .catch(error => {
            console.error("Error sending face for user: " + username + ". Error: ", error);
        });
    }

    function showQuestion(questionNumber) {
        $('.question-block').removeClass('active');
        $(`#question${questionNumber}`).addClass('active');
        updateNavButtons();
    }

    function showNextQuestion() {
        if (currentQuestion < totalQuestions) {
            if (!isAnswered(currentQuestion)) {
                $(`#paletteItem${currentQuestion}`).addClass('incorrect');
            }
            currentQuestion++;
            showQuestion(currentQuestion);
        }
    }

    function showPreviousQuestion() {
        if (currentQuestion > 1) {
            currentQuestion--;
            showQuestion(currentQuestion);
        }
    }

    function scrollToQuestion(questionNumber) {
        currentQuestion = questionNumber;
        showQuestion(currentQuestion);
    }

    function clearResponse(questionNumber) {
        $(`#question${questionNumber} input[type="radio"]`).prop('checked', false);
        updateQuestionStatus(questionNumber);
    }

    function isAnswered(questionNumber) {
        let answered = false;
        $(`#question${questionNumber} input[type="radio"]`).each(function() {
            if ($(this).is(':checked')) {
                answered = true;
            }
        });
        return answered;
    }

    function updateQuestionStatus(questionNumber) {
        let answered = isAnswered(questionNumber);
        let paletteItem = $(`#paletteItem${questionNumber}`);
        if (answered) {
            paletteItem.removeClass('incorrect');
            paletteItem.addClass('correct');
        } else {
            paletteItem.removeClass('correct');
            paletteItem.addClass('incorrect');
        }

        updateSummary();
    }

    function updateSummary() {
        let answeredCount = 0;
        let unansweredCount = 0;
        for (let i = 1; i <= totalQuestions; i++) {
            let paletteItem = $(`#paletteItem${i}`);
            if (paletteItem.hasClass('correct')) {
                answeredCount++;
            } else {
                unansweredCount++;
            }
        }
        $('#answeredCount').text(answeredCount);
        $('#unansweredCount').text(unansweredCount);
    }

    function updateNavButtons() {
        if (currentQuestion === 1) {
            $('#prevBtn').hide();
        } else {
            $('#prevBtn').show();
        }

        if (currentQuestion === totalQuestions) {
            $('#nextBtn').hide();
        } else {
            $('#nextBtn').show();
        }
    }

    document.addEventListener("keydown", function(event) {
        if (event.repeat) return;
        if (keyPressed) {
            console.log("Unusual activity detected.");
            Swal.fire({
                title: "Unusual Activity Detected",
                text: "Please avoid multiple key presses.",
                icon: "warning",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = "student_dashboard.php";
            });
            return;
        }
        console.log("First key press detected.");
        Swal.fire({
            title: "Warning",
            text: "Please avoid multiple key presses.",
            icon: "warning"
        });
        keyPressed = true;
    });

    document.addEventListener("visibilitychange", function() {
        if (document.visibilityState === 'hidden') {
            alert("Warning: You switched tabs or minimized the window.");
            tabSwitchCount++;
            if (tabSwitchCount >= 2) {
                alert("Warning: Excessive tab switches detected.");
                window.location.href = "student_dashboard.php";
            }
        }
    });

    document.getElementById("markAttendanceBtn").addEventListener('click', function () {
        enterFullScreen();
    });

    function enterFullScreen() {
        const docElm = document.documentElement;
        if (docElm.requestFullscreen) {
            docElm.requestFullscreen();
        } else if (docElm.mozRequestFullScreen) {
            docElm.mozRequestFullScreen();
        } else if (docElm.webkitRequestFullscreen) {
            docElm.webkitRequestFullscreen();
        } else if (docElm.msRequestFullscreen) {
            docElm.msRequestFullscreen();
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === "Escape") {
            exitFullScreen();
        }
    });

    function exitFullScreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
</script>
</body>
</html>
