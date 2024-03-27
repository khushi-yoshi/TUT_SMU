<?php
// Start the session
session_start();

// Initialize an array to store error messages
$errorMsg = [];

// Include your database connection details here
$host = 'smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com'; //DB Hostname
$db_username = 'admin'; //Username
$db_password = 'SmuTeam1?'; // Password
$database = 'smu_peer_eval'; // DB Name

// Create a new database connection
$mysqli = new mysqli($host, $db_username, $db_password, $database);

// Check for a database connection error
if ($mysqli->connect_error) {
    $errorMsg[] = "Connection failed: " . $mysqli->connect_error;
} else {
    // If the connection is successful and the form is submitted via POST method, proceed with form processing
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form data and sanitize inputs
        $firstName = $mysqli->real_escape_string(trim($_POST['firstName']));
        $lastName = $mysqli->real_escape_string(trim($_POST['lastName']));
        $email = $mysqli->real_escape_string(trim($_POST['email']));
        $password = $mysqli->real_escape_string(trim($_POST['password']));
        $role = $mysqli->real_escape_string(trim($_POST['role']));

        // SQL query to check if the email is already registered
        $checkEmailQuery = "SELECT * FROM student WHERE STUD_EMAIL = ? UNION SELECT * FROM professor WHERE PROF_EMAIL = ?";
        $checkEmailStmt = $mysqli->prepare($checkEmailQuery);
        $checkEmailStmt->bind_param('ss', $email, $email);
        $checkEmailStmt->execute();
        $checkEmailResult = $checkEmailStmt->get_result();

        // If the email is already registered, add an error message
        if ($checkEmailResult->num_rows > 0) {
            $errorMsg[] = "This email is already registered. Please use a different email.";
        } else {
            // If the email is not registered, proceed to insert the user into the appropriate table based on the selected role
            if ($role === 'professor') {
                // If the role is professor, generate a unique ID for the professor
                $result = $mysqli->query("SELECT MAX(PROF_ID) as max_id FROM professor");
                $row = $result->fetch_assoc();
                $max_id = $row ? $row['max_id'] : 0;
                $next_id = $max_id + 1;

                // Prepare SQL statement to insert data into the professor table
                $stmt = $mysqli->prepare("INSERT INTO professor (PROF_ID, PROF_FNAME, PROF_LNAME, PROF_EMAIL, PROF_PASS) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('issss', $next_id, $firstName, $lastName, $email, $password);
            } elseif ($role === 'student') {
                // If the role is student, generate a unique ID for the student
                $result = $mysqli->query("SELECT MAX(STUD_ID) as max_id FROM student");
                $row = $result->fetch_assoc();
                $max_id = $row ? $row['max_id'] : 0;
                $next_id = $max_id + 1;

                // Prepare SQL statement to insert data into the student table
                $stmt = $mysqli->prepare("INSERT INTO student (STUD_ID, STUD_FNAME, STUD_LNAME, STUD_EMAIL, STUD_PASS) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('issss', $next_id, $firstName, $lastName, $email, $password);
                
            } else {
                // If an invalid role is selected, add an error message
                $errorMsg[] = "Invalid role selected.";
            }

            // If there are no errors and the SQL statement is prepared, execute it
            if (empty ($errorMsg) && isset ($stmt)) {
                // If the execution is successful, redirect the user to index.php
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "Registration successful! Please log in.";
                    header("Location: index.php");
                    exit;
                } else {
                    // If execution fails, add an error message
                    $errorMsg[] = "Registration failed: " . $stmt->error;
                }
                // Close the prepared statement
                $stmt->close();
            }
        }
        // Close the statement for checking email existence
        $checkEmailStmt->close();
    }
    // Close the database connection
    $mysqli->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- Create an application that is compatible across multiple devices (desktop, mobile,tablet)!-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | SMU</title>
    <link href="../styles/style.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div class="logo-container">
        <!-- SMU Logo -->
        <img src="../assets/trans_logo.jpeg" alt="SMU Logo" id="smu-logo" style="width: 200px; height: auto;">
    </div>
    <div class="container">
        <h2>Sign Up</h2> <!-- Sign Up Heading -->
        <?php
        // Display error messages if any
        if (!empty ($errorMsg)) {
            foreach ($errorMsg as $message) {
                echo "<p class='error-msg'>$message</p>";
            }
        }
        ?>

        <!-- Sign Up Form -->
        <form id="signup-form" method="post" class="login-form">
            <div class="form-input">
                <!-- First Name Input -->
                <label for="firstName">First Name</label>
                <input type="text" id="firstName" name="firstName" required placeholder="First Name">
            </div>
            <div class="form-input">
                <!-- Last Name Input -->
                <label for="lastName">Last Name</label>
                <input type="text" id="lastName" name="lastName" required placeholder="Last Name">
            </div>
            <div class="form-input">
                <!--Email Input -->
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="example@smu.edu.sg">
            </div>
            <div class="form-input">
                <!-- Password Input -->
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Password">
            </div>
            <div class="form-input">
                <label for="role">Select Role</label>
                <select id="role" name="role" required>
                    <!--Role selection dropdown -->
                    <option value="">--Please choose an option--</option>
                    <option value="student">Student</option>
                    <option value="professor">Professor</option>
                </select>
            </div>
            <div class="form-submit">
                <!-- SUBMIT BUTTON -->
                <input type="submit" value="Sign Up" class="submit-button">
            </div>
        </form>
    </div>

    <!-- SMU disclaimer-->
    <div class="footer">
        <p>DISCLAIMER: This website is not the official SMU website. It is a prototype and a student project. There is
            no endorsement by SMU. <a href="https://www.smu.edu.sg/"
                style="color: #fff; text-decoration: underline;">Here's the official SMU website</a>.</p>
    </div>

    <!-- JavaScript to validate role selection -->
    <script>
        document.getElementById('signup-form').onsubmit = function (e) {
            var role = document.getElementById('role').value;
            if (role === "") {
                e.preventDefault(); // Prevent form submission
                alert('Please select a role.');
            }
        }
    </script>

</body