<?php
session_start();

$db_hostname = "localhost";
$db_username = "root";
$db_password = "1qaz#EDC";
// $db_password = "";
$db_name = "wifi_registration";

// --- Database radius (radcheck) ---
$radius_db_hostname = "localhost";
$radius_db_username = "root";
$radius_db_password = "1qaz#EDC";
// $radius_db_password = "";
$radius_db_name   = "radius";

/* เชื่อม DB */
$conn = db_connect();
$conn_radius = db_connect_radius();

/* ==============================
   MAIN
============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("index.php");
}

$ref_code = trim($_POST['ref_code']);
$otp_code = trim($_POST['otp_code']);
$current_time = date("Y-m-d H:i:s");
$user_id = $_SESSION['wifi_forgot_password_form']['user_id'];
$username = $_SESSION['wifi_forgot_password_form']['radius_username'];
$phone_number = $_SESSION['wifi_forgot_password_form']['phone'];

// --- Check OTP Code ---
$stmt = $conn->prepare("SELECT * FROM otp_registration WHERE ref_code=? AND otp_code=? AND expired_at>?");
$stmt->bind_param("sss", $ref_code, $otp_code, $current_time);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows == 0) { showError("รหัสยืนยันตัวตนไม่ถูกต้อง หรือหมดอายุแล้ว"); }
$stmt->close();

// --- Insert Agreements ---
update_otp_registration($conn, $user_id, $ref_code, $otp_code, $current_time);

// --- Generate temporary password ---
$temp_password = bin2hex(random_bytes(4)); // 8 chars

// --- เข้ารหัสรหัสผ่านด้วย crypt แบบ SHA-512 ---
$salt = '$6$' . substr(str_replace('+', '.', base64_encode(random_bytes(16))), 0, 16);
$temp_hash = crypt($temp_password, $salt);

send_otp_api($conn, $username, $temp_password, $phone_number);

$success_redcheck = update_redcheck($conn_radius, $temp_hash, $username);

$success_users = update_users($conn, $temp_password, $username);

if ($success_redcheck && $success_users) {
    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid #0c0;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<h2 style='color:green;'>✅ รีเซ็ตรหัสผ่านสำเร็จ</h2>";
    echo "<p>กรุณาตรวจสอบรหัสผ่านชั่วคราวที่ส่งไปทาง SMS ตามหมายเลขมือถือที่ลงทะเบียนไว้ กรุณาเปลี่ยนรหัสผ่านหลังจากเข้าสู่ระบบ</p>";
    echo "<p><a href='index.php' style='color:blue;text-decoration:underline;'>กลับไปหน้าหลัก</a></p>";
    echo "</div>";
} else {
    showMessage("❌ เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน");
}

/* ==============================
   FUNCTIONS
============================== */
/* ---------------- */
function db_connect(){
    global $db_hostname, $db_username, $db_password, $db_name;

    $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
    if ($conn->connect_error) {
        die("Wifi_registration DB connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    return $conn;
}

/* ---------------- */
function db_connect_radius(){
    global $radius_db_hostname, $radius_db_username, $radius_db_password, $radius_db_name;

    $conn = new mysqli($radius_db_hostname, $radius_db_username, $radius_db_password, $radius_db_name);
    if ($conn->connect_error) {
        die("Radius DB connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    return $conn;
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
function send_otp_api($conn, $username, $temp_password, $phone_number){
    $base_url_sms = "https://smsgw.mybynt.com/service/SMSWebServiceEngine.php";
    $method = "POST";
    $phone_number = $phone_number;
    $current_time = date("Y-m-d H:i:s");

    // $message = "ม.รามคำแหง OTP: $otp_code (Ref: $ref_code) ใช้ได้ภายใน 5 นาที";
    $message = "รหัสผ่านชั่วคราวของคุณคือ: $temp_password";

    /* Escape ข้อความสำหรับ XML */
    $message_xml = htmlspecialchars($message, ENT_XML1, 'UTF-8');

    if($current_time >= '2026-02-16 00:00:00'){
        $user = "0830001481";
        $pass = "07yu9N";
        $sender = "GCB4RamWiFi";
    } else {
        $user = "ramotp_test";
        $pass = "QfjXjI";
        $sender = "Gcb256802";
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <Envelope>
            <Header/>
            <Body>
            <sendSMS>
            <user>'. $user .'</user>
            <pass>'. $pass .'</pass>
            <from>'. $sender .'</from>
            <target>'. htmlspecialchars($phone_number, ENT_XML1, 'UTF-8') .'</target>
            <mess>'. $message_xml .'</mess>
            <lang>T</lang>
            </sendSMS>
            </Body>
            </Envelope>';

    $curl = curl_init();

    /* table logs_api (optional) */
    $stmt = $conn->prepare("INSERT INTO logs_api (mobile_no, uri, method, data_post, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $phone_number, $base_url_sms, $method, $xml, $current_time);
    if(!$stmt->execute()){
        die("Execute failed: ".$stmt->error);
    }
    $logs_api_id = $stmt->insert_id;

    curl_setopt_array($curl, [
        CURLOPT_URL => $base_url_sms,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $xml,
        CURLOPT_HTTPHEADER => [
            "Content-Type: text/xml; charset=UTF-8"
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
        die("cURL Error: " . curl_error($curl));
    }
    
    curl_close($curl);

    // parse XML
    $xml = simplexml_load_string($response);

    // register namespace
    $xml->registerXPathNamespace('ns1', 'http://localhost/service/');

    // ดึงค่า <return>
    $result = $xml->xpath('//ns1:sendSMSResponse/return');

    $response_xml = (string) ($result[0] ?? '');

    $data_response_xml = trim($response_xml);
    $data_response = trim($response);

    preg_match('/^\[(\d+)\]\s*(.*)$/', $data_response_xml, $match);
    $response_transaction = $match[1] ?? null;
    $response_message     = $match[2] ?? null;

    // --- Update wifi_registration.logs_api with created_at ---
    $stmt = $conn->prepare("UPDATE logs_api SET data_response=?, data_transaction=?, data_message=?, updated_at=? WHERE id=?");
    $stmt->bind_param("ssssi", $data_response, $response_transaction, $response_message, $current_time, $logs_api_id);
    $stmt->execute();
    $stmt->close();
}

/* ---------------- */
function update_redcheck($conn_radius, $temp_hash, $username){
    // --- Update radcheck (Crypt-Password) ---
    $stmt = $conn_radius->prepare("UPDATE radcheck SET value=? WHERE username=? AND attribute='Crypt-Password'");
    $stmt->bind_param("ss", $temp_hash, $username);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

/* ---------------- */
function update_users($conn, $temp_password, $username){
    // --- Update wifi_registration.users (เก็บรหัสผ่านแบบ plain หรือ hash ก็ได้ตามโครงสร้างเดิม) ---
    $radius_password = md5($temp_password);
    $stmt = $conn->prepare("UPDATE users SET radius_password=? WHERE radius_username=?");
    $stmt->bind_param("ss", $radius_password, $username);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

// --- Function to show error and back link ---
function showMessage($message, $success=false) {
    $color = $success ? "green" : "red";
    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid $color;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<p style='color:$color;'>$message</p>";
    echo "<p><a href='index.php' style='color:blue;text-decoration:underline;'>กลับไปยังฟอร์ม</a></p>";
    echo "</div>";
    exit;
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
function redirect($url){
    header("Location: ". $url);
    exit;
}
