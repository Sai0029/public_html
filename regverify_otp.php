<?php
session_start();
include("php/config.php");

date_default_timezone_set('Asia/Kolkata');

if (isset($_POST['verify_otp'])) {
    if (!isset($_SESSION['temp_email'])) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Session expired. Please try again.',
                        confirmButtonText: 'Retry'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'index.php';
                        }
                    });
                });
              </script>";
        exit();
    }

    $entered_otp = $_POST['otp'];
    $email = $_SESSION['temp_email'];

    $query = "SELECT otp, otp_expiry FROM student_users WHERE mail='$email'";
    $result = mysqli_query($con, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($con));
    }

    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $stored_otp = $row['otp'];
        $otp_expiry = $row['otp_expiry'];

        $expiry_time = new DateTime($otp_expiry);
        $current_time = new DateTime();

        if ($entered_otp == $stored_otp && $current_time < $expiry_time) {
            $update_query = "UPDATE student_users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE mail = '$email'";
            if (mysqli_query($con, $update_query)) {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Your account has been successfully verified!',
                                confirmButtonText: 'Login'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'stu_login.php';
                                }
                            });
                        });
                      </script>";
                unset($_SESSION['temp_email']);
                exit();
            } else {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Verification failed. Please try again.',
                                confirmButtonText: 'Retry'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'regverify_otp.php';
                                }
                            });
                        });
                      </script>";
            }
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid OTP',
                            text: 'The OTP you entered is invalid or has expired. Please try again.',
                            confirmButtonText: 'Retry'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'regverify_otp.php';
                            }
                        });
                    });
                  </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="style/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
    <div class="box form-box">
        <header>OTP Verification</header>
        <form action="" method="post">
            <div class="field input">
                <label for="otp">Enter OTP</label>
                <input type="text" name="otp" id="otp" required>
                <span class="error-message"></span>
            </div>
            <div class="field">
                <input type="submit" class="btn" name="verify_otp" value="Verify OTP">
            </div>
        </form>
    </div>
</div>
</body>
</html>
