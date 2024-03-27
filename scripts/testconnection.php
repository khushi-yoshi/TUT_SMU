<?php
$db_host = 'smupeereval.cr0uq86ii30w.us-east-1.rds.amazonaws.com';
$db_username = 'admin';
$db_password = 'SmuTeam1?';
$db_name = 'smu_peer_eval';

// Create connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully<br>";
    
    // Query to get all tables in the database
    $query_show_tables = "SHOW TABLES";
    
    // Execute the query to get all tables
    $result_show_tables = $conn->query($query_show_tables);
    
    // Check if query executed successfully
    if ($result_show_tables) {
        // Fetch each table
        while ($row_show_tables = $result_show_tables->fetch_row()) {
            $table_name = $row_show_tables[0];
            echo "<h2>Table: $table_name</h2>";
            
            // Query to fetch all rows from the current table
            $query_select_rows = "SELECT * FROM $table_name";
            
            // Execute the query to select all rows
            $result_select_rows = $conn->query($query_select_rows);
            
            // Check if query executed successfully
            if ($result_select_rows) {
                // Fetch and print each row
                if ($result_select_rows->num_rows > 0) {
                    echo "<table border='1'>";
                    while ($row_select_rows = $result_select_rows->fetch_assoc()) {
                        echo "<tr>";
                        foreach ($row_select_rows as $key => $value) {
                            echo "<td>$key: $value</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table><br>";
                } else {
                    echo "No rows found in table $table_name<br>";
                }
            } else {
                echo "Error selecting rows from table $table_name: " . $conn->error . "<br>";
            }
        }
    } else {
        echo "Error: " . $conn->error;
    }

    // Close connection
    $conn->close();
}
?>
