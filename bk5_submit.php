<?php
// --- Database wifi_registration ---
$servername = "localhost";
$username = "root";
$password = "1qaz#EDC";
$dbname = "wifi_registration";

// Connect DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Insert into users
$stmt = $conn->prepare("INSERT INTO users (fullname, user_type, staff_id, student_id, citizen_id, phone, email_facebook)
VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss",
    $_POST['fullname'],
    $_POST['user_type'],
    $_POST['staff_id'],
    $_POST['student_id'],
    $_POST['citizen_id'],
    $_POST['phone'],
    $_POST['email_facebook']
);
$stmt->execute();
$user_id = $stmt->insert_id;
$stmt->close();

// Step 2: Insert into agreements (no signed_name, signed_date)
$accept = isset($_POST['accept_terms']) ? 1 : 0;
$stmt = $conn->prepare("INSERT INTO agreements (user_id, accept_terms) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $accept);
$stmt->execute();
$stmt->close();


// --- Database radius (radcheck) ---
$radius_host = "localhost";
$radius_user = "root";
$radius_pass = "1qaz#EDC";
$radius_db   = "radius";

// Connect to radius DB
$radius_conn = new mysqli($radius_host, $radius_user, $radius_pass, $radius_db);
if ($radius_conn->connect_error) {
    die("Connection to radius failed: " . $radius_conn->connect_error);
}

// Generate random username and password for RADIUS
$rad_username = "user" . $user_id;              // unique
$rad_password = bin2hex(random_bytes(4));       // random 8 chars

// Insert user into radcheck table
$stmt = $radius_conn->prepare("INSERT INTO radcheck (username, attribute, op, value)
VALUES (?, 'Cleartext-Password', ':=', ?)");
$stmt->bind_param("ss", $rad_username, $rad_password);
$stmt->execute();
$stmt->close();
$radius_conn->close();

// --- Update wifi_registration.users with RADIUS credentials ---
$stmt = $conn->prepare("UPDATE users SET radius_username=?, radius_password=? WHERE user_id=?");
$stmt->bind_param("ssi", $rad_username, $rad_password, $user_id);
$stmt->execute();
$stmt->close();

// --- Close wifi_registration connection ---
$conn->close();

// --- Show confirmation to user ---
echo "<div style='font-family:sans-serif;padding:20px;border:1px solid #ccc;border-radius:10px;max-width:400px;margin:20px auto;'>";
echo "<h2>✅ การลงทะเบียนสำเร็จแล้ว</h2>";
echo "<p><b>Username:</b> $rad_username</p>";
echo "<p><b>Password:</b> $rad_password</p>";
echo "<p>กรุณาจดจำหรือบันทึกข้อมูลนี้เพื่อเข้าใช้งาน Wi-Fi</p>";
echo "</div>";

// Optional: redirect back to captive portal after 10 sec
// header("Refresh:10; URL=http://login.ru.ac.th");
?>
