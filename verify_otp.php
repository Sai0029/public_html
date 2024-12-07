<?php
session_start();
include("php/config.php");
date_default_timezone_set('Asia/Kolkata');

if (isset($_POST['submit_otp'])) {
    $otp = mysqli_real_escape_string($con, $_POST['otp']);
    $email = $_SESSION['temp_email'];

    // Check if OTP is correct and not expired
    $result = mysqli_query($con, "SELECT * FROM student_users WHERE mail='$email' AND otp='$otp' AND otp_expiry > NOW()");
    if (mysqli_num_rows($result) > 0) {
        // Update verification status
        $update_query = "UPDATE student_users SET is_verified=1, otp=NULL, otp_expiry=NULL WHERE mail='$email'";
        if (mysqli_query($con, $update_query)) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Account Verified',
                            text: 'Your account has been verified. You can now log in.',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'stu_login.php';
                            }
                        });
                    });
                  </script>";
        } else {
            echo "Error updating verification status: " . mysqli_error($con);
        }
    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid OTP',
                        text: 'The OTP you entered is incorrect or has expired.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'verify_otp.php';
                        }
                    });
                });
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="style/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
    <div class="box form-box">
        <header>Verify OTP</header>
        <form action="" method="post">
            <div class="field input">
                <label for="otp">Enter OTP</label>
                <input type="text" name="otp" id="otp" autocomplete="off" required>
            </div>
            <div class="field">
                <input type="submit" class="btn" name="submit_otp" value="Verify OTP">
            </div>
        </form>
    </div>
</div>
</body>
</html>
