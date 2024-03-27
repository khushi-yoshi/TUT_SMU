<?php
// Start the session
session_start();

// Redirect to the login page if the user is not logged in or not a student
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    header('Location: index.php');
    exit;
}

// Include the logout script when the logout link is clicked
if (isset($_GET['logout'])) {
    // Perform logout actions
    session_destroy(); // Destroy the session
    header('Location: index.php'); // Redirect to login page after logout
    exit;
}

// Database connection parameters for AWS RDS
$host = 'smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com';
$db_username = 'admin';
$db_password = 'SmuTeam1?';
$database = 'smu_peer_eval';

// Attempt to establish a connection to MySQL database
$mysqli = new mysqli($host, $db_username, $db_password, $database);

// Check if the connection was successful
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Get the student's ID from the session
$studentID = $_SESSION['user_id'] ?? null;

// Prepare SQL statement to fetch the image link for the student
$imageQuery = "SELECT link FROM images WHERE role = 'student'";
$imageStmt = $mysqli->prepare($imageQuery);

$imageStmt->execute();

$imageStmt->bind_result($imageLink);

$imageStmt->fetch();

$imageStmt->close();

// Prepare SQL statement to fetch courses that the student is enrolled in
$query = "SELECT c.COURSE_ID, c.COURSE_NAME FROM course c
          JOIN team t ON c.COURSE_ID = t.COURSE_ID
          JOIN team_member tm ON t.TEAM_ID = tm.TEAM_ID
          WHERE tm.STUD_ID = ?";
$stmt = $mysqli->prepare($query);

// Bind the parameter
$stmt->bind_param("i", $studentID);

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
    <title>Welcome Student</title>
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
        <!-- Display the image retrieved from the database -->
        <img src="<?php echo htmlspecialchars($imageLink); ?>" alt="Student Image" class="student-image">
        <h1>Welcome
            <?php echo htmlspecialchars($_SESSION['fname']); ?>!
        </h1>
        <form action="studentcourse.php" method="get">
            <label for="course">Select a course:</label>
            <select name="course" id="course" required>
                <option value="">Please select a course</option>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo $row['COURSE_ID']; ?>">
                        <?php echo htmlspecialchars($row['COURSE_NAME']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="continue-button">View Course</button>
        </form>
    </div>

    <script>
        function setFormAction() {
            var courseSelect = document.getElementById('course');
            var selectedCourse = courseSelect.options[courseSelect.selectedIndex].value;
            var form = document.querySelector('form');
            form.action = "studentcourse.php?course=" + encodeURIComponent(selectedCourse);
        }

        document.getElementById('course').addEventListener('change', setFormAction);
        window.onload = setFormAction;
    </script>
</body>
</html>
