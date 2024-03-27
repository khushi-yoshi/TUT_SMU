<?php
// Start a new session or resume the existing session
session_start();

// Check if the user is logged in, if not redirect to the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

//DB config
$db_host = 'smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com';
$db_username = 'admin';
$db_password = 'SmuTeam1?';
$db_name = 'smu_peer_eval';

// attempt to connect to the datbase through 'pdo'
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_username, $db_password);
    // set pdo error mode to exception to catch any errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    //if connection fail, stop script and show error msg
    die("ERROR: Could not connect. " . $e->getMessage());
}

//define columns expected to be present in the CSV file
$requiredColumns = [
    'student id', 
    'student first name', 
    'student last name', 
    'student email', 
    'team id'
];

//intialize vars for error/success BEFORE the if 
$fileError = $contentStatus = "";


// Check if the server request is POST and the file is uploaded
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["courseFile"])) {
    
    // Get the mime type of the uploaded file
    $fileType = $_FILES["courseFile"]["type"];
    // Define the allowed types for uploaded files
    $allowedFileTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];

    //check if files type is among allowed types
    if (in_array($fileType, $allowedFileTypes)) {
        // Open the uploaded file for reading
        if (($handle = fopen($_FILES["courseFile"]["tmp_name"], "r")) !== FALSE) {
            //Read first line to get headers
            $header = fgetcsv($handle);
            // make header names lowercase for comparing
            $headerNormalized = array_map(function($e) { return strtolower(trim($e)); }, $header);
            //normalize required column names too
            $requiredColumnsNormalized = array_map('strtolower', $requiredColumns);
            //check for ANY missing columns in the file
            $missingColumns = array_diff($requiredColumnsNormalized, $headerNormalized);
            
            //if no columns are missing, proceed with import
            if (empty($missingColumns)) {
                //start db transaction
                $pdo->beginTransaction();
                try {
                    // Prepare details from FORM input
                    $courseName = trim($_POST["courseName"]);
                    $courseNum = trim($_POST["coursenum"]);
                    $courseID = trim($_POST["courseID"]);
                    $courseSemester = trim($_POST["courseSemester"]);
                    $year = trim($_POST["year"]);
                    $courseTime = trim($_POST["courseTime"]);
                    $profID = $_SESSION['user_id']; // Assuming this session variable contains the logged-in professor's ID

                    //get professors ID FROM SESSION
                    $stmt = $pdo->prepare("INSERT INTO course (COURSE_ID, COURSE_NUM, COURSE_NAME, COURSE_YEAR, COURSE_TERM, COURSE_TIME, PROF_ID) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$courseID, $courseNum, $courseName, $year, $courseSemester, $courseTime, $profID]);

                    // Loop through each line in the CSV file and insert the student details
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        // Prepare student insertion
                        $stmt = $pdo->prepare("INSERT INTO student (STUD_ID, STUD_FNAME, STUD_LNAME, STUD_EMAIL) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$row[0], $row[1], $row[2], $row[3]]);
                        
                        // Prepare team_member insertion
                        $teamID = $row[4];
                        $stmt = $pdo->prepare("INSERT INTO team_member (TEAM_ID, STUD_ID) VALUES (?, ?)");
                        $stmt->execute([$teamID, $row[0]]);
                    }
                    
                    //COMMIT IF ALL INSERTS WERE SUCCESS
                    $pdo->commit();
                    //success message if all went well
                    $contentStatus = "Course and students imported successfully.";
                    // Redirect or handle successful import UNCOMMENT WHEN EVEYRHTING WORKS
                    // header("Location: imported_course.php");
                    // exit;
                } catch (PDOException $e) {
                    // If an error occurs, roll back the transaction
                    $pdo->rollBack();
                    $fileError = "Database error: " . $e->getMessage();
                }
            } else {
                //if req column missing throw this
                $fileError = "Content error: Missing required columns (" . implode(', ', $missingColumns) . ").";
            }   
            fclose($handle);
        } else {
            $fileError = "Invalid file type. Please upload a valid CSV file.";
        }
    } else {
        $fileError = "File upload error. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Import Form</title>
    <link href="../styles/course_import.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../assets/trans_logo.jpeg" alt="SMU Logo" id="smu-logo">
            <h1>Course Import Form</h1>
        </div>

        <!-- Go Back button -->
        <div class="form-group">
            <button onclick="goBack()">Go Back</button>
        </div>

        <div class="import-instructions">
            <p>Click below to import your course. Only .CSV files are permitted.</p>
            <p>Please ensure your data file is correctly formatted according to the requirements below:</p>
            <img src="../assets/Example.jpg" alt="Example file format" style="max-width: 650px;">
        </div>

        <!-- Display file content status or error message -->
        <?php if ($fileError): ?>
            <p class="error"><?php echo $fileError; ?></p>
        <?php elseif ($contentStatus): ?>
            <p class="success"><?php echo $contentStatus; ?></p>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="courseName">Course Name:</label>
                <input type="text" name="courseName" id="courseName" required>

                <label for="courseName">Course NUM:</label>
                <input type="text" name="coursenum" id="coursenum" required>

                <label for="courseID">Course ID:</label>
                <input type="text" name="courseID" id="courseID" required>
                <label for="courseTime" style="display: block; margin-bottom: 5px;">Course Time:</label>
<input type="time" name="courseTime" id="courseTime" required style="width: 94%; padding: 8px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px;">

                <label for="courseSemester">Course Semester:</label>
                <select name="courseSemester" id="courseSemester" required>
                    <option value="Spring">Spring</option>
                    <option value="Fall">Fall</option>
                    <option value="Winter">Winter</option>
                    <option value="Summer">Summer</option>
                </select>

                <label for="year">Year:</label>
                <select name="year" id="year" required>
                    <?php
                    $currentYear = date("Y");
                    for ($i = $currentYear; $i <= $currentYear + 5; $i++) {
                        echo "<option value='$i'>$i</option>";
                    }
                    ?>
                </select>
                

                <label for="courseFile">Upload Course CSV:</label>
<input type="file" name="courseFile" id="courseFile" accept=".csv" required>

                <button type="submit" name="submit">Submit</button>
            </div>
        </form>

        <div class="notes">
            <p>*CSV file should include columns for Student ID, Student First Name, Student Last Name, Student Email, and Student Group.</p>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>