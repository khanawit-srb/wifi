<?php
// Database credentials
$host = "localhost";
$user = "root";
$password = "1qaz#EDC";
$dbname = "radius";

// Connect to database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete request
if (isset($_GET['delete_table']) && isset($_GET['delete_id'])) {
    $delete_table = $conn->real_escape_string($_GET['delete_table']);
    $delete_id = $conn->real_escape_string($_GET['delete_id']);

    // หาคอลัมน์แรกของตาราง เพื่อใช้เป็น primary key (สมมติ)
    $result = $conn->query("SHOW COLUMNS FROM `$delete_table`");
    if ($result && $result->num_rows > 0) {
        $first_column = $result->fetch_assoc()['Field'];
        // Delete query
        $sql = "DELETE FROM `$delete_table` WHERE `$first_column` = '$delete_id' LIMIT 1";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>Deleted row with $first_column = $delete_id from table $delete_table.</p>";
        } else {
            echo "<p style='color: red;'>Delete failed: " . $conn->error . "</p>";
        }
    }
}

// Get all tables
$tablesResult = $conn->query("SHOW TABLES");

if ($tablesResult->num_rows > 0) {
    while ($tableRow = $tablesResult->fetch_array()) {
        $tableName = $tableRow[0];
        echo "<h2>Table: $tableName</h2>";

        $dataResult = $conn->query("SELECT * FROM `$tableName`");

        if ($dataResult->num_rows > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";

            // Table headers
            echo "<tr>";
            while ($field = $dataResult->fetch_field()) {
                echo "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            echo "<th>Action</th>";  // เพิ่มคอลัมน์ปุ่มลบ
            echo "</tr>";

            // Table rows
            $dataResult->data_seek(0);
            while ($row = $dataResult->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }

                // สมมติว่า คอลัมน์แรกของแถวเป็น key
                $first_col_name = array_key_first($row);
                $first_col_value = $row[$first_col_name];

                // ปุ่มลบ
                $delete_link = "?delete_table=" . urlencode($tableName) . "&delete_id=" . urlencode($first_col_value);
                echo "<td><a href='$delete_link' onclick=\"return confirm('Are you sure to delete this row?');\">Delete</a></td>";

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
