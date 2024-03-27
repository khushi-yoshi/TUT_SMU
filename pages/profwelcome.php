<?php
// Start the session
session_start();

// Redirect to the login page if the user is not logged in or not a professor
if (!isset ($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'professor') {
    header('Location: index.php');
    exit;
}

// Include the logout script when the logout link is clicked
if (isset ($_GET['logout'])) {
    // Perform logout actions
    session_destroy(); // Destroy the session
    header('Location: index.php'); // Redirect to login page after logout
    exit;
}

// Database connection parameters for AWS RDS
$host = 'smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com'; // RDS endpoint
$db_username = 'admin'; // RDS username
$db_password = 'SmuTeam1?'; // RDS password
$database = 'smu_peer_eval'; // Database name

// Attempt to establish a connection to MySQL database
$mysqli = new mysqli($host, $db_username, $db_password, $database);

// Check if the connection was successful
if ($mysqli->connect_errno) {
    die ("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Get the professor's ID from the session
$professorID = $_SESSION['user_id'] ?? null;

// Prepare SQL statement to fetch courses taught by the professor
$query = "SELECT COURSE_ID, COURSE_NAME FROM course WHERE PROF_ID = ?";
$stmt = $mysqli->prepare($query);

// Bind the parameter
$stmt->bind_param("i", $professorID);

// Execute the query
$stmt->execute();

// Get result
$result = $stmt->get_result();

// Close the prepared statement
$stmt->close();

// Close the database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Professor</title>
    <link href="../styles/welcome.css" rel="stylesheet" type="text/css">
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="../assets/trans_logo.jpeg" alt="SMU Logo" class="logo">
        </div>
        <button class="logout-button" onclick="location.href='?logout'">Log out</button>
    </header>
    <div class="container">
        <h1>Welcome Professor
            <?php echo htmlspecialchars($_SESSION['lname']); ?>!
        </h1>
        <form action="profcourse.php" method="get">
            <label for="course">Select course to continue:</label>
            <select name="course" id="course" required>
                <option value="">Select course to continue</option>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo $row['COURSE_ID']; ?>">
                        <?php echo htmlspecialchars($row['COURSE_NAME']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="continue-button">Continue</button>
        </form>
        <div class="or-section">OR</div>
        <button onclick="location.href='course_import.php'" class="add-course-button">Add a course</button>
    </div>


    <script>
        function setFormAction() {
            var courseSelect = document.getElementById('course');
            var selectedCourseName = courseSelect.options[courseSelect.selectedIndex].text;
            var form = document.querySelector('form');
            // You need to determine the correct URL pattern for your application
            form.action = "profcourse.php?course=" + encodeURIComponent(selectedCourseName);
        }
        // Ensure the form's action is set when the course selection changes
        document.getElementById('course').addEventListener('change', setFormAction);
        // Call setFormAction on page load in case the form is submitted without changing the dropdown
        window.onload = setFormAction;
    </script>


</body>

</html>