<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csvFile"])) {
    $requiredColumns = [
        'student_id', 
        'fname', 
        'lname', 
        'email', 
        'group'
    ];
    

    $fileType = $_FILES["csvFile"]["type"];
    $allowedFileTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];

    if (in_array($fileType, $allowedFileTypes)) {
        if (($handle = fopen($_FILES["csvFile"]["tmp_name"], "r")) !== FALSE) {
            $header = fgetcsv($handle);
            $headerNormalized = array_map(function($e) { return strtolower(trim($e)); }, $header);
            $missingColumns = array_diff($requiredColumns, $headerNormalized);

            if (!empty($missingColumns)) {
                echo "Warning: Missing required columns (" . implode(', ', $missingColumns) . ").<br>";
            } else {
                echo "<h2>CSV Content:</h2>";
                echo "<table border='1'>";
                echo "<tr>";
                foreach ($header as $columnName) {
                    echo "<th>" . htmlspecialchars($columnName) . "</th>";
                }
                echo "</tr>";

                while (($row = fgetcsv($handle)) !== FALSE) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
            fclose($handle);
        } else {
            echo "Error: Unable to open the file.";
        }
    } else {
        echo "Invalid file type. Please upload a valid CSV file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload CSV</title>
</head>
<body>
    <form action="Table_data.php" method="post" enctype="multipart/form-data">
        <label for="csvFile">Choose CSV file:</label>
        <input type="file" id="csvFile" name="csvFile" accept=".csv">
        <button type="submit">Upload</button>
    </form>
</body>
</html>
