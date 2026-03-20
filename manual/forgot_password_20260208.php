<?php
// --- Database configs ---
// $radius_cfg = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"radius"];
$radius_cfg = ["host"=>"localhost","user"=>"root","pass"=>"","db"=>"radius"];
// $wifi_cfg   = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"wifi_registration"];
$wifi_cfg   = ["host"=>"localhost","user"=>"root","pass"=>"","db"=>"wifi_registration"];

// Connect radius DB
$radius_conn = new mysqli($radius_cfg["host"], $radius_cfg["user"], $radius_cfg["pass"], $radius_cfg["db"]);
if ($radius_conn->connect_error) die("Radius DB connection failed: " . $radius_conn->connect_error);

// Connect wifi_registration DB
$wifi_conn = new mysqli($wifi_cfg["host"], $wifi_cfg["user"], $wifi_cfg["pass"], $wifi_cfg["db"]);
if ($wifi_conn->connect_error) die("Wifi_registration DB connection failed: " . $wifi_conn->connect_error);

// --- Function to show message ---
function showMessage($message, $success=false) {
    $color = $success ? "green" : "red";
    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid $color;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<p style='color:$color;'>$message</p>";
    echo "<p><a href='index.php' style='color:blue;text-decoration:underline;'>กลับไปยังฟอร์ม</a></p>";
    echo "</div>";
    exit;
}

// --- Process POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? ''; // citizen_id หรือ email

    if (empty($identifier)) showMessage("กรุณากรอกเลขบัตรประชาชนหรืออีเมล");

    // --- Lookup user in wifi_registration ---
    $stmt = $wifi_conn->prepare("SELECT radius_username, email FROM users WHERE citizen_id=? OR email=?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $stmt->bind_result($username, $email);
    if (!$stmt->fetch()) {
        $stmt->close();
        showMessage("ไม่พบผู้ใช้นี้");
    }
    $stmt->close();

    // --- Generate temporary password ---
    $temp_password = bin2hex(random_bytes(4)); // 8 chars

    // --- เข้ารหัสรหัสผ่านด้วย crypt แบบ SHA-512 ---
    $salt = '$6$' . substr(str_replace('+', '.', base64_encode(random_bytes(16))), 0, 16);
    $temp_hash = crypt($temp_password, $salt);

    // --- Update radcheck (Crypt-Password) ---
    $stmt = $radius_conn->prepare("UPDATE radcheck SET value=? WHERE username=? AND attribute='Crypt-Password'");
    $stmt->bind_param("ss", $temp_hash, $username);
    $ok1 = $stmt->execute();
    $stmt->close();

    // --- Update wifi_registration.users (เก็บรหัสผ่านแบบ plain หรือ hash ก็ได้ตามโครงสร้างเดิม) ---
    $stmt = $wifi_conn->prepare("UPDATE users SET radius_password=? WHERE radius_username=?");
    $stmt->bind_param("ss", md5($temp_password), $username);
    $ok2 = $stmt->execute();
    $stmt->close();

    $radius_conn->close();
    $wifi_conn->close();

    if ($ok1 && $ok2) {
        showMessage("✅ รหัสผ่านชั่วคราวของคุณคือ: <b>$temp_password</b><br>กรุณาเปลี่ยนรหัสผ่านหลังจากเข้าสู่ระบบ", true);
    } else {
        showMessage("❌ เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน");
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ลืมรหัสผ่าน Wi-Fi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="custom-style.css">
</head>
<body>
<div class="container">
    <h1>ลืมรหัสผ่าน Wi-Fi</h1>
    <form method="post">
        <div class="form-group">
            <label>เลขบัตรประชาชน หรือ อีเมล</label>
            <input type="text" name="identifier" required placeholder="เลขบัตรประชาชน หรือ อีเมล">
        </div>
        <div class="text-center">
            <button type="submit">รีเซ็ตรหัสผ่าน</button>
        </div>
    </form>
</div>
</body>
</html>
