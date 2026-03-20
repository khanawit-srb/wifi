<?php
include_once('app.php');

session_start();

$campus_code = $_SESSION['campus_code'];
$campus_type = $_SESSION['campus_type'];

$db_hostname = "localhost";
$db_username = "root";
// $db_password = "1qaz#EDC";
$db_password = "";
$db_name = "wifi_registration";

// --- Database radius (radcheck) ---
$radius_db_hostname = "localhost";
$radius_db_username = "root";
// $radius_db_password = "1qaz#EDC";
$db_password = "";
$radius_db_name   = "radius";

/* เชื่อม DB */
$conn = db_connect();
$conn_radius = db_connect_radius();

/* ==============================
   MAIN
============================== */
//if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//    redirect("index.php");
//}

$ref_code = trim($_POST['ref_code']);
$otp_code = trim($_POST['otp_code']);
$current_time = date("Y-m-d H:i:s");

// --- Check OTP Code ---
$stmt = $conn->prepare("SELECT * FROM otp_registration WHERE ref_code=? AND otp_code=? AND expired_at>?");
$stmt->bind_param("sss", $ref_code, $otp_code, $current_time);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows == 0) { showError("รหัสยืนยันตัวตนไม่ถูกต้อง หรือหมดอายุแล้ว"); }
$stmt->close();

// --- Insert User ---
$user_id = insert_user($conn, $current_time);

// --- Insert Agreements ---
insert_agreement($conn, $user_id);

// --- Insert Agreements ---
update_otp_registration($conn, $user_id, $ref_code, $otp_code, $current_time);

// Use citizen_id as rad_username
$rad_username = $_SESSION['wifi_form']['citizen_id'];
$rad_password = bin2hex(random_bytes(4)); // random 8-character plain password

// --- เข้ารหัสแบบ SHA-512 โดยใช้ crypt() ---
$salt = '$6$' . substr(str_replace('+', '.', base64_encode(random_bytes(16))), 0, 16);
$crypt_password = crypt($rad_password, $salt);

insert_radcheck($conn_radius, $rad_username, $crypt_password);

update_user($conn, $user_id, $rad_username, $rad_password);

session_destroy();

$url_login = ($campus_type == "WIFI") ? $campus_wifi[$campus_code] : $campus_lan[$campus_code];

// --- Show confirmation to user ---
echo "<div style='font-family:sans-serif;padding:20px;border:1px solid #0c0;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<h2 style='color:green;'>✅ การลงทะเบียนสำเร็จแล้ว</h2>";
    echo "<p><b>Username:</b> $rad_username </p>";
    echo "<p><b>Password:</b> $rad_password </p>";
    echo "<p>กรุณาจดจำหรือบันทึกข้อมูลนี้เพื่อเข้าใช้งาน Wi-Fi</p>";
    echo "<div style='display: flex;align-items: center;justify-content: space-evenly;'>";
        echo "<a href='index.php' style='color:blue;text-decoration:underline;'>กลับไปหน้าหลัก</a>";
        if($campus_code != ""){
            echo "<a href=' $url_login ' style='color:blue;text-decoration:underline;'>เข้าสู่ระบบ</a>";
        }
    echo "</div>";
echo "</div>";

/* ==============================
   FUNCTIONS
============================== */

/* ---------------- */
function db_connect(){
    global $db_hostname, $db_username, $db_password, $db_name;

    $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    return $conn;
}

/* ---------------- */
function db_connect_radius(){
    global $radius_db_hostname, $radius_db_username, $radius_db_password, $radius_db_name;

    $conn = new mysqli($radius_db_hostname, $radius_db_username, $radius_db_password, $radius_db_name);
    if ($conn->connect_error) {
        die("Connection Radius  failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    return $conn;
}

// --- Function to show error and back link ---
function showError($message) {
    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid #f00;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<h2 style='color:red;'>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p> $message </p>";
    echo "<p><a href='registration.php' style='color:blue;text-decoration:underline;'>กลับไปหน้าลงทะเบียนใหม่</a></p>";
    echo "</div>";
    exit;
}

/* ---------------- */
function insert_user($conn, $current_time){

    $fullname = $_SESSION['wifi_form']['fullname'];
    $citizen_id = $_SESSION['wifi_form']['citizen_id'];
    $phone = $_SESSION['wifi_form']['phone'];
    $email = $_SESSION['wifi_form']['email'];

    /* table users (optional) */
    $stmt = $conn->prepare("INSERT INTO users (fullname, citizen_id, phone, email, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fullname, $citizen_id, $phone, $email, $current_time);
    $stmt->execute();
    $user_id = $stmt->insert_id;

    return $user_id;
}

/* ---------------- */
function insert_agreement($conn, $user_id){

    /* table agreements (optional) */
    $accept_terms = isset($_SESSION['wifi_form']['accept_terms']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO agreements (user_id, accept_terms) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $accept_terms);
    $stmt->execute();
    $stmt->close();
}

/* ---------------- */
function update_otp_registration($conn, $user_id, $ref_code, $otp_code, $current_time){
    
    // --- Update wifi_registration.otp_registration with created_at ---
    $status_verify = 1;
    $stmt = $conn->prepare("UPDATE otp_registration SET user_id=?, status_verify=?, updated_at=? WHERE ref_code=? AND otp_code=? AND expired_at>?");
    $stmt->bind_param("iissss", $user_id, $status_verify, $current_time, $ref_code, $otp_code, $current_time);
    $stmt->execute();
    $stmt->close();
}

/* ---------------- */
function insert_radcheck($conn_radius, $rad_username, $crypt_password){

    $stmt = $conn_radius->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Crypt-Password', ':=', ?)");
    $stmt->bind_param("ss", $rad_username, $crypt_password);
    $stmt->execute();
    $stmt->close();
    $conn_radius->close();
}

/* ---------------- */
function update_user($conn, $user_id, $rad_username, $rad_password){
    
    $rad_password_md5 = md5($rad_password);

    // --- Update wifi_registration.users with RADIUS credentials ---
    $stmt = $conn->prepare("UPDATE users SET radius_username=?, radius_password=? WHERE user_id=?");
    $stmt->bind_param("ssi", $rad_username, $rad_password_md5, $user_id);
    $stmt->execute();
    $stmt->close();
}
