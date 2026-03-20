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
$stmt->bind_param("sssssss", $_POST['fullname'], $_POST['user_type'], $_POST['staff_id'], $_POST['student_id'], $_POST['citizen_id'], $_POST['phone'], $_POST['email_facebook']);
$stmt->execute();
$user_id = $stmt->insert_id;
$stmt->close();

// Step 2: Insert into devices
$stmt = $conn->prepare("INSERT INTO devices (user_id, device_type, device_brand_model, os, asset_number)
VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $_POST['device_type'], $_POST['device_brand_model'], $_POST['os'], $_POST['asset_number']);
$stmt->execute();
$stmt->close();

// Step 3: Insert into requests
$stmt = $conn->prepare("INSERT INTO requests (user_id, access_type, purpose, university_branch)
VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $_POST['access_type'], $_POST['purpose'], $_POST['university_branch']);
$stmt->execute();
$stmt->close();

// Step 4: Insert into agreements
$accept = isset($_POST['accept_terms']) ? 1 : 0;
$stmt = $conn->prepare("INSERT INTO agreements (user_id, accept_terms, signed_name, signed_date)
VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $user_id, $accept, $_POST['signed_name'], $_POST['signed_date']);
$stmt->execute();
$stmt->close();

// ❌ Removed $conn->close() here (too early)

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
$rad_username = "user" . $user_id;              // ใช้ user_id ทำให้ unique
$rad_password = bin2hex(random_bytes(4));      // รหัสผ่านสุ่ม 8 ตัวอักษร

// Insert user into radcheck table
$stmt = $radius_conn->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)");
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
$conn->close();   // ✅ only here

// --- Show confirmation to user ---
echo "✅ การลงทะเบียนสำเร็จแล้ว ขอบคุณที่ใช้บริการ!<br>";
echo "Username สำหรับ Wi-Fi: <b>$rad_username</b><br>";
echo "Password: <b>$rad_password</b><br>";
?>
