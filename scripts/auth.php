<?php
// auth.php: Handles the validation of the login form

function validateLogin($username, $password) {
    // Database connection parameters
    $host = "192.12.243.215";
    $Port = "3306";
    $db_username = "User1";
    $db_password = "SmuTeam1?";
    $database = "smu_peer_eval";

    // Attempt to establish a connection to MySQL database
    $mysqli = new mysqli($host, $Port, $db_username, $db_password, $database);

    // Check if the connection was successful
    if ($mysqli->connect_errno) {
        die("Failed to connect to MySQL: " . $mysqli->connect_error);
    }

    // Sanitize input
    $username = $mysqli->real_escape_string($username);
    $password = $mysqli->real_escape_string($password);

    // Attempt to validate login credentials for professor first
    $query = "SELECT * FROM professor WHERE PROF_EMAIL = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['PROF_PASS'] == $password) { // Password verification should use password_verify if you're hashing your passwords
            session_start();
            $_SESSION['user_id'] = $row['PROF_ID'];
            $_SESSION['role'] = 'professor';
            $_SESSION['loggedin'] = true;
            $mysqli->close();
            header("Location: profwelcome.php");
            exit;
        } else {
            $mysqli->close();
            return "Incorrect password. Please try again.";
        }
    }

    // If professor not found or password does not match, try student
    $query = "SELECT * FROM student WHERE STUD_EMAIL = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['STUD_PASS'] == $password) { // Password verification should use password_verify if you're hashing your passwords
            session_start();
            $_SESSION['user_id'] = $row['STUD_ID'];
            $_SESSION['role'] = 'student';
            $_SESSION['loggedin'] = true;
            $mysqli->close();
            header("Location: studentwelcome.php");
            exit;
        } else {
            $mysqli->close();
            return "Incorrect password. Please try again.";
        }
    }

    // Close the database connection
    $mysqli->close();

    // If neither professor nor student is found
    return "User not found with the given username.";
}
?>
