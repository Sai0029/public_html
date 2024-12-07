<?php
session_start();
include("php/config.php");
date_default_timezone_set('Asia/Kolkata');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'C:\Users\Sai\Downloads\PHPMailer-master\src\Exception.php';
require 'C:\Users\Sai\Downloads\PHPMailer-master\src\PHPMailer.php';
require 'C:\Users\Sai\Downloads\PHPMailer-master\src\SMTP.php';

if (isset($_GET['email'])) {
    $email = mysqli_real_escape_string($con, $_GET['email']);

    // Check if email exists and is not verified
    $check_email_query = mysqli_query($con, "SELECT * FROM student_users WHERE mail='$email'");
    if (mysqli_num_rows($check_email_query) > 0) {
        $row = mysqli_fetch_assoc($check_email_query);
        $is_verified = $row['is_verified'];

        if ($is_verified == 1) {
            // Email is already verified
            echo "<script>
                    Swal.fire({
                        icon: 'info',
                        title: 'Already Verified',
                        text: 'Your email is already verified.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'stu_login.php';
                        }
                    });
                  </script>";
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            // Store OTP and expiry time in the database
            $update_query = "UPDATE student_users SET otp='$otp', otp_expiry='$otp_expiry' WHERE mail='$email'";
            if (mysqli_query($con, $update_query)) {
                // Send OTP to user's email
                $_SESSION['temp_email'] = $email;

                // Create an instance of PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'sainagineni2002@gmail.com';
                    $mail->Password = 'uheksmkpmpgrdccz';
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port = 465;

                    // Recipients
                    $mail->setFrom('sainagineni2002@gmail.com', 'SAIKRISHNA');
                    $mail->addAddress($email);
                    $mail->addReplyTo('sainagineni2002@gmail.com', 'SAIKRISHNA');

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body = 'Your OTP code is ' . $otp;
                    // Send the email
                    $mail->send();
                    echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'OTP Sent',
                                text: 'An OTP has been sent to your email address.',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'verify_otp.php';
                                }
                            });
                          </script>";
                } catch (Exception $e) {
                    echo "<script>
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Send OTP',
                                text: 'Mailer Error: {$mail->ErrorInfo}',
                                confirmButtonText: 'OK'
                            });
                          </script>";
                }
            } else {
                echo "Error updating OTP: " . mysqli_error($con);
            }
        }
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Email Not Found',
                    text: 'The email you entered does not exist in our records.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'stu_login.php';
                    }
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
    <title>Verify Account</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
<div class="container">
    <div class="box form-box">
        <header>Verify Account</header>
        <form action="verify_otp.php" method="post">
            <div class="field input">
                <label for="email">Enter your Email</label>
                <input type="email" name="email" id="email" autocomplete="off" required>
            </div>
            <div class="field">
                <input type="submit" class="btn" name="submit" value="Send OTP">
            </div>
        </form>
    </div>
</div>
</body>
</html>
