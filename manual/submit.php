<?php
session_start();

$db_hostname = "localhost";
$db_username = "root";
$db_password = "1qaz#EDC";
// $db_password = "";
$db_name = "wifi_registration";

/* เชื่อม DB */
$conn = db_connect();

/* ==============================
   CONFIG
============================== */
define('OTP_EXPIRE_MINUTE', 5);

/* ==============================
   MAIN
============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("index.php");
}

// --- Validate required fields ---
if (empty($_POST['fullname']) || empty($_POST['citizen_id']) || empty($_POST['phone']) || empty($_POST['email'])) {
    showError("กรุณากรอกข้อมูลชื่อ, เลขบัตรประชาชน, เบอร์โทรศัพท์ และอีเมลให้ครบ");
}

// --- Validate Thai citizen_id ---
$citizen_id = $_POST['citizen_id'];
if (!isValidThaiCitizenID($citizen_id)) {
    showError("เลขบัตรประชาชนไม่ถูกต้อง");
}

// --- Validate email ---
$email = $_POST['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showError("รูปแบบอีเมลไม่ถูกต้อง");
}

// --- Check duplicate citizen_id ---
$stmt = $conn->prepare("SELECT user_id FROM users WHERE citizen_id=?");
$stmt->bind_param("s", $citizen_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    showError("เลขบัตรประชาชนนี้ได้ลงทะเบียนแล้ว");
}
$stmt->close();

/* รับค่าฟอร์ม */
$form_data = get_form_data();

/* gen otp */
$otp_data = generate_otp();

/* เก็บ session */
store_session($form_data, $otp_data);

/* log OTP (optional table) */
insert_otp_log($conn, $form_data, $otp_data);

/* ส่ง OTP (API Placeholder) */
// send_otp_api_test($form_data, $otp_data);
send_otp_api($conn, $form_data['phone'], $otp_data['otp_code'], $otp_data['ref_code'], $otp_data['expired_at']);

/* redirect */
redirect("otp_registration.php");

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

// --- Function to show error and back link ---
function showError($message) {
    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid #f00;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<h2 style='color:red;'>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p>$message</p>";
    echo "<p><a href='registration.php' style='color:blue;text-decoration:underline;'>กลับไปหน้าลงทะเบียนใหม่</a></p>";
    echo "</div>";
    exit;
}

/* ---------------- */
function get_form_data(){

    return [
        'fullname'      => trim($_POST['fullname']),
        'citizen_id'    => trim($_POST['citizen_id']),
        'phone'         => trim($_POST['phone']),
        'email'         => trim($_POST['email']),
        'accept_terms'  => trim($_POST['accept_terms']),
    ];
}

// --- Thai citizen_id validator ---
function isValidThaiCitizenID($id) {
    if (!preg_match('/^\d{13}$/', $id)) return false;
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += intval($id[$i]) * (13 - $i);
    }
    $check_digit = (11 - ($sum % 11)) % 10;
    return intval($id[12]) === $check_digit;
}

/* ---------------- */
function generate_otp(){

    $ref_code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"),0,4);
    $otp_code = rand(100000,999999);
    $expired_at = date("Y-m-d H:i:s", strtotime("+". OTP_EXPIRE_MINUTE ." minutes"));

    return [
        'ref_code'  => $ref_code,
        'otp_code'  => $otp_code,
        'expired_at'=> $expired_at
    ];
}

/* ---------------- */
function store_session($form_data, $otp_data){

    $_SESSION['wifi_form']  = $form_data;
    $_SESSION['ref_code']   = $otp_data['ref_code'];
    $_SESSION['expired_at'] = $otp_data['expired_at'];
}

/* ---------------- */
function insert_otp_log($conn, $form_data, $otp_data){

    $phone_number = $form_data['phone'];
    $ref_code = $otp_data['ref_code'];
    $otp_code = $otp_data['otp_code'];
    $expired_at = $otp_data['expired_at'];
    $action = 'register';
    $status_verify = 0;
    $created_at = DATE("Y-m-d H:i:s");

    /* table otp_logs (optional) */
    $stmt = $conn->prepare("INSERT INTO otp_registration (phone_number, ref_code, otp_code, expired_at, action, status_verify, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $phone_number, $ref_code, $otp_code, $expired_at, $action, $status_verify, $created_at);
    if(!$stmt->execute()){
        die("Execute failed: ".$stmt->error);
    }
}

/* ----------------
   API SEND OTP
---------------- */
function send_otp_api_test($form_data, $otp_data){
    
    file_put_contents("otp_registration.log", "Ref Code : ". $otp_data['ref_code'] ."OTP Code : ". $otp_data['otp_code'] ."EXPIRE : ". $otp_data['expired_at'] .PHP_EOL);
}

function send_otp_api($conn, $phone_number, $otp_code, $ref_code, $expired_at){

    $base_url_sms = "https://smsgw.mybynt.com/service/SMSWebServiceEngine.php";
    $method = "POST";
    $phone_number = $phone_number;
    $current_time = date("Y-m-d H:i:s");

    // $message = "ม.รามคำแหง OTP: $otp_code (Ref: $ref_code) ใช้ได้ภายใน 5 นาที";
    // $message = "รหัสยืนยัน (OTP) ของคุณคือ {$otp_code} รหัสอ้างอิง {$ref_code} กรุณาใช้ภายใน 5 นาที และห้ามเปิดเผยรหัสนี้กับผู้อื่น";
    $message = "OTP: {$otp_code} (อ้างอิง: {$ref_code}) ใช้ได้ภายใน 5 นาที";

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
function redirect($url){
    header("Location: ". $url);
    exit;
}