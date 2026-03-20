<?php
// Database credentials
$host = "localhost";
$user = "root";         // replace with your DB username
$password = "1qaz#EDC"; // replace with your DB password
$dbname = "wifi_registration";

// Connect to database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all tables
$tablesResult = $conn->query("SHOW TABLES");

if ($tablesResult->num_rows > 0) {
    while ($tableRow = $tablesResult->fetch_array()) {
        $tableName = $tableRow[0];
        echo "<h2>Table: $tableName</h2>";

        $dataResult = $conn->query("SELECT * FROM $tableName");

        if ($dataResult->num_rows > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            
            // Table headers
            echo "<tr>";
            while ($field = $dataResult->fetch_field()) {
                echo "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            echo "</tr>";

            // Table rows
            $dataResult->data_seek(0); // reset pointer
            while ($row = $dataResult->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table><br>";
        } else {
            echo "No data found in table '$tableName'.<br><br>";
        }
    }
} else {
    echo "No tables found in database '$dbname'.";
}

$conn->close();
?>
