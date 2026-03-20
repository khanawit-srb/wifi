<?php
$servername = "localhost";
$username = "root";      // เปลี่ยนเป็น user ของ MySQL
$password = "1qaz#EDC";          // ใส่รหัสผ่านของ MySQL
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

$conn->close();

echo "✅ การลงทะเบียนสำเร็จแล้ว ขอบคุณที่ใช้บริการ!";
?>
