<?php
// Start the session
session_start();

// Redirect to the login page if the user is not logged in
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
$host = 'smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com'; // RDS endpoint
$db_username = 'admin'; // RDS username
$db_password = 'SmuTeam1?'; // RDS password
$database = 'smu_peer_eval'; // Database name

// Attempt to establish a connection to MySQL database
$mysqli = new mysqli($host, $db_username, $db_password, $database);

// Check if the connection was successful
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Get the student's ID from the session
$studentID = $_SESSION['user_id'] ?? null;

// Fetch courses for the student
if ($studentID !== null) {
    // Prepare SQL statement to fetch courses the student is enrolled in
    $query = "
        SELECT c.COURSE_NAME 
        FROM course c
        INNER JOIN team_member tm ON c.COURSE_ID = tm.COURSE_ID
        WHERE tm.STUD_ID = ?
    ";
    $stmt = $mysqli->prepare($query);
    
    // Bind the parameter
    $stmt->bind_param("i", $studentID);
    
    // Execute the query
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    
    // Close the prepared statement
    $stmt->close();
} else {
    $result = [];
}

// Close the database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Student</title>
    <!-- Include your stylesheet here -->
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../assets/trans_logo.jpeg" alt="SMU Logo" class="logo">
        </div>
        <button class="logout-button" onclick="location.href='?logout'">Log out</button>
    </header>
    <div class="container">
        <div class="welcome-heading">
            <h2>Welcome <?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?>!</h2>
        </div>
        <form action="student_course_selection.php" method="post">
            <select name="course" required>
                <option value="">Select your course</option>
                <!-- Iterate over result and populate options -->
                <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['COURSE_NAME']); ?>">
                        <?php echo htmlspecialchars($row['COURSE_NAME']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Go to Course</button>
        </form>
    </div>

    <!-- Include additional JavaScript files here -->
</body>
</html>
