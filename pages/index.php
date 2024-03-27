<?php
session_start(); // Start the session at the beginning of the script

$errorMsg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the submitted username and password
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Database connection parameters for AWS RDS
    $host = 'smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com'; // RDS endpoint
    $db_username = 'admin'; // username
    $db_password = 'SmuTeam1?'; // password
    $database = 'smu_peer_eval'; // Database name

    // Attempt to establish a connection to MySQL database
    $mysqli = new mysqli($host, $db_username, $db_password, $database);

    // Check if the connection was successful
    if ($mysqli->connect_errno) {
        $errorMsg = "Failed to connect to MySQL: " . $mysqli->connect_error;
    } else {
        // Sanitize input
        $username = $mysqli->real_escape_string($username);
        $password = $mysqli->real_escape_string($password);

        // Query to validate login credentials for PROFESSOR
        $query = "SELECT * FROM professor WHERE PROF_EMAIL = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $profResult = $stmt->get_result();

        // Check if professor's login credentials are valid
        if ($profRow = $profResult->fetch_assoc()) {
            if ($profRow['PROF_PASS'] == $password) {
                // Set session variables for professor
                $_SESSION['loggedin'] = true;
                $_SESSION['role'] = 'professor';
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $profRow['PROF_ID'];
                $_SESSION['lname'] = $profRow['PROF_LNAME'];
                $mysqli->close();
                header("Location: profwelcome.php");
                exit;
            } else {
                $errorMsg = "Incorrect password. Please try again.";
            }
        } else {
            // Query to validate login credentials for STUDENT
            $query = "SELECT * FROM student WHERE STUD_EMAIL = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $studResult = $stmt->get_result();

            // Check if student's login credentials are valid
            if ($studRow = $studResult->fetch_assoc()) {
                if ($studRow['STUD_PASS'] == $password) {
                    // Set session variables for student
                    $_SESSION['loggedin'] = true;
                    $_SESSION['role'] = 'student';
                    $_SESSION['username'] = $username;
                    $_SESSION['user_id'] = $studRow['STUD_ID'];
                    $_SESSION['fname'] = $studRow['STUD_FNAME'];
                    $_SESSION['lname'] = $studRow['STUD_LNAME'];
                    $mysqli->close();
                    header("Location: studentwelcome.php");
                    exit;
                } else {
                    $errorMsg = "Incorrect password. Please try again.";
                }
            } else {
                $errorMsg = "No user found with the given username.";
            }
        }
        $mysqli->close(); // CLOSE database connection
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    
    <!-- Create an application that is compatible across multiple devices (desktop, mobile,tablet)!-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="../styles/style.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div class="logo-container">
        <img src="../assets/trans_logo.jpeg" alt="SMU Logo" id="smu-logo" style="width: 200px; height: auto;"> 
    </div>
    <div class="container">
        <h2>Welcome Back</h2> <!-- Heading -->
        <?php if (!empty($errorMsg)): ?>
            <p class="error-msg"><?php echo $errorMsg; ?></p> <!-- Display error message if exists -->
        <?php endif; ?>

        <!-- Login Form -->
        <form method="post" action="index.php" class="login-form">

            <div class="form-input">
                <!-- Username (Email) Input -->
                <label for="username">Username (Email)</label>
                <input type="email" id="username" name="username" placeholder="example@smu.edu.sg" required>
            </div>
            <div class="form-input">
                <!-- Password Input -->
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-submit">
                <!-- Submit Button TO LOGIN and Sign Up Button -->
                <button type="submit" class="submit-button">Login</button>
                &nbsp;&nbsp;&nbsp;
                <button type="button" class="submit-button" onclick="window.location.href='signup.php';">Sign Up</button>
            </div>
        </form>
    </div>

    <!-- Disclaimer for anyone who tries to sign up -->
    <div style="position: fixed; bottom: 0; left: 0; width: 100%; background-color: #333; color: #ff0000; padding: 20px; text-align: center; font-size: 14px;">
    <p>DISCLAIMER: This website is not the official SMU website. It is a prototype and a student project. There is no endorsement by SMU. <a href="https://www.smu.edu.sg/" style="color: #fff; text-decoration: underline;">Here's the official SMU website</a>.</p>
</div>

</body>
</html>