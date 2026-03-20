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

// --- Function to show error and back link ---
function showError($message) {
    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid #f00;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<h2 style='color:red;'>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p>$message</p>";
    echo "<p><a href='registration.php' style='color:blue;text-decoration:underline;'>กลับไปยังหน้าลงทะเบียน</a></p>";
    echo "</div>";
    exit;
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

// Step 1: Insert into users
$stmt = $conn->prepare("INSERT INTO users (fullname, citizen_id, phone, email) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss",
    $_POST['fullname'],
    $citizen_id,
    $_POST['phone'],
    $email
);
$stmt->execute();
$user_id = $stmt->insert_id;
$stmt->close();

// Step 2: Insert into agreements
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
    showError("ไม่สามารถเชื่อมต่อ RADIUS server ได้");
}

// Use citizen_id as rad_username
$rad_username = $citizen_id;
$rad_password = bin2hex(random_bytes(4)); // random 8 chars

// Insert into radcheck
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

// --- Close connection ---
$conn->close();

// --- Show confirmation to user ---
echo "<div style='font-family:sans-serif;padding:20px;border:1px solid #0c0;border-radius:10px;max-width:400px;margin:20px auto;'>";
echo "<h2 style='color:green;'>✅ การลงทะเบียนสำเร็จแล้ว</h2>";
echo "<p><b>Username:</b> $rad_username</p>";
echo "<p><b>Password:</b> $rad_password</p>";
echo "<p>กรุณาจดจำหรือบันทึกข้อมูลนี้เพื่อเข้าใช้งาน Wi-Fi</p>";
echo "<p><a href='registration.php' style='color:blue;text-decoration:underline;'>กลับไปหน้าลงทะเบียนใหม่</a></p>";
echo "</div>";
?>
