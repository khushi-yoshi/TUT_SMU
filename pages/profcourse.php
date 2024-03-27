<?php
// Start the session
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset ($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// More logout logic
if (isset($_GET['logout'])) {
    // Destroy session
    session_destroy();
    // Redirect to the login page
    header('Location: index.php');
    exit;
}

// DATABASE CONNECTION PARAMETERS
$host = "smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com";
$db_username = "admin";
$db_password = "SmuTeam1?";
$database = "smu_peer_eval";

//Establish DB Connection
$mysqli = new mysqli($host, $db_username, $db_password, $database);

// Check for connection errors
if ($mysqli->connect_error) {
    die ("Connection failed: " . $mysqli->connect_error);
}

// Get selected course ID from URL, default to empty string if not set
// THE JAVASCRIPT FUNCTION FROM PROFWELCOME.PHP HELPED US ENSURE THAT THE CORRECT COURSE
// IS APPENDED TO THE END OF THE URL
$selectedCourseId = $_GET['course'] ?? '';

// Get sort order and search term from URL, default to appropriate defaults if not set
$sortOrder = $_GET['sortOrder'] ?? 'STUD_LNAME';
$searchTerm = $_GET['searchTerm'] ?? '';
$searchTermFormatted = "%$searchTerm%";
$professorID = $_SESSION['user_id'];

// Query to fetch the selected course name
$courseNameQuery = "SELECT COURSE_NAME FROM course WHERE COURSE_ID = ?";
$courseStmt = $mysqli->prepare($courseNameQuery);
$courseStmt->bind_param("i", $selectedCourseId);
$courseStmt->execute();
$courseStmt->store_result();
$courseStmt->bind_result($selectedCourseName);
$courseStmt->fetch();
$courseStmt->close();

// SQL query to fetch student details based on selected course, professor ID, search term, and sort order
$sql = "SELECT s.STUD_ID, s.STUD_FNAME, s.STUD_LNAME, s.STUD_EMAIL, t.TEAM_NAME 
        FROM student s
        JOIN team_member tm ON s.STUD_ID = tm.STUD_ID
        JOIN team t ON tm.TEAM_ID = t.TEAM_ID
        JOIN course c ON t.COURSE_ID = c.COURSE_ID
        WHERE c.PROF_ID = ? 
        AND (s.STUD_ID LIKE ? OR s.STUD_FNAME LIKE ? OR s.STUD_LNAME LIKE ? OR s.STUD_EMAIL LIKE ? OR t.TEAM_NAME LIKE ?)
        ORDER BY $sortOrder";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("isssss", $professorID, $searchTermFormatted, $searchTermFormatted, $searchTermFormatted, $searchTermFormatted, $searchTermFormatted);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <!-- Create an application that is compatible across multiple devices (desktop, mobile,tablet)!-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($selectedCourseName); ?> - Course Details
    </title>
    <!--styling sheet external reference-->
    <link href="../styles/operations.css" rel="stylesheet" type="text/css">
</head>

<body>
    <header>
        <!-- Logo container -->
        <div class="logo-container">
            <img src="../assets/trans_logo.jpeg" alt="SMU Logo" class="logo" style="width: 200px; height: auto;">
        </div>

        <!-- Home and logout buttons -->
        <button class="home-button" onclick="location.href='profwelcome.php'">Home</button>
        <button class="logout-button" onclick="location.href='?logout'">Log out</button>
    </header>

    <div class="container">
        <!-- Title of Course (black font) and buttons for Peer Eval -->
        <div class="title-container">
            <h1>
                <?php echo htmlspecialchars($selectedCourseName); ?>
            </h1>
            <div class="buttons">
                <button onclick="location.href='schedule-peer-evaluation.php'">Schedule Peer Evaluation</button>
                <button onclick="location.href='view-peer-evaluations.php'">View Peer Evaluations</button>
            </div>
        </div>

        <!-- Form for searching and sorting -->
        <form method="get" action="profcourse.php">
            <input type="text" name="searchTerm" placeholder="Search here to filter..."
                value="<?php echo $searchTerm; ?>">


            <!-- Dropdown options for sorting -->
            <select name="sortOrder">
                <!-- Option to sort by last name -->
                <option value="STUD_LNAME" <?php echo $sortOrder == 'STUD_LNAME' ? 'selected' : ''; ?>>Last Name</option>
                <!-- Option to sort by first name -->
                <option value="STUD_FNAME" <?php echo $sortOrder == 'STUD_FNAME' ? 'selected' : ''; ?>>First Name</option>
                <!-- Option to sort by Student ID -->
                <option value="STUD_ID" <?php echo $sortOrder == 'STUD_ID' ? 'selected' : ''; ?>>Student ID</option>
            </select>

            <!-- Buttom to submit the form -->
            <button type="submit">Search</button>

            <!-- Hidden field to maintain selected course across our searches //stores the number of the course ID -->
            <input type="hidden" name="course" value="<?php echo htmlspecialchars($selectedCourseId); ?>">
        </form>

        <!-- Table Formatting!!!-->
        <div class="table-container">
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Student Email</th>
                    <th>Group #</th>
                </tr>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['STUD_ID']) . "</td>
                                <td>" . htmlspecialchars($row['STUD_FNAME']) . "</td>
                                <td>" . htmlspecialchars($row['STUD_LNAME']) . "</td>
                                <td>" . htmlspecialchars($row['STUD_EMAIL']) . "</td>
                                <td>" . htmlspecialchars($row['TEAM_NAME']) . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No students found</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>

</html>