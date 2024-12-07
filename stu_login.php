<?php
session_start();

date_default_timezone_set('Asia/Kolkata');
include("php/config.php");

if(isset($_POST['submit'])){
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // Client-side validation for email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='message'>
            <p>Invalid email format</p>
        </div> <br>";
        echo "<a href='index.html'><button class='btn'>Go Back</button>";
        exit(); // Stop further execution
    }

    // Check if the email and password are correct and the account is verified
    $result = mysqli_query($con, "SELECT * FROM student_users WHERE mail='$email' AND password='$password'") or die("Select Error");
    $row = mysqli_fetch_assoc($result);

    if ($row && $row['is_verified'] == 1) {
        $_SESSION['valid'] = $row['mail'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['age'] = $row['age'];
        $_SESSION['id'] = $row['id'];
        header("Location: student_dashboard.php");
        exit(); // Make sure to exit after redirection
    } elseif ($row && $row['is_verified'] == 0) {
        // Account is not verified
        echo "<div class='message'>
              <p>Your account is not verified. Please verify your account before logging in.</p>
              <button class='btn' onclick=\"verifyAccount('$email')\">Verify Account</button>
              </div> <br>";
    } else {
        // Invalid credentials
        echo "<div class='message'>
              <p>Wrong Username or Password</p>
              </div> <br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Student Login</title>
</head>
<body>
<div class="container">
    <div class="box form-box">
        <header>Student Login</header>
        <form action="" method="post">
            <div class="field input">
                <label for="email">Email</label>
                <input type="text" name="email" id="email" autocomplete="off" required>
            </div>

            <div class="field input">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" autocomplete="off" required>
            </div>

            <div class="field">    
                <input type="submit" class="btn" name="submit" value="Login" required>
            </div>
            <div class="links">
                Don't have an account? <a href="Register.php">Sign Up Now</a>
            </div>
        </form>
    </div>
</div>

<script>
// Function to handle verifying account using SweetAlert
function verifyAccount(email) {
    Swal.fire({
        title: 'Verify Account',
        text: 'Your account is not verified. Do you want to verify it now?',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Verify',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to verify_account.php with email as a query parameter
            window.location.href = `verify_account.php?email=${email}`;
        }
    });
}
</script>

</body>
</html>
