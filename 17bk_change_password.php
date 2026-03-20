<?php
// --- Database configs ---
$radius_cfg = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"radius"];
$wifi_cfg   = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"wifi_registration"];

// Connect radius DB
$radius_conn = new mysqli($radius_cfg["host"], $radius_cfg["user"], $radius_cfg["pass"], $radius_cfg["db"]);
if ($radius_conn->connect_error) {
    die("Radius DB connection failed: " . $radius_conn->connect_error);
}

// Connect wifi_registration DB
$wifi_conn = new mysqli($wifi_cfg["host"], $wifi_cfg["user"], $wifi_cfg["pass"], $wifi_cfg["db"]);
if ($wifi_conn->connect_error) {
    die("Wifi_registration DB connection failed: " . $wifi_conn->connect_error);
}

// --- Function to show message ---
function showMessage($message, $success = false) {
    $color = $success ? "green" : "red";
    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid $color;border-radius:10px;max-width:400px;margin:20px auto;'>";
    echo "<p style='color:$color;'>$message</p>";
    echo "<p><a href='index.php' style='color:blue;text-decoration:underline;'>กลับไปยังหน้าฟอร์ม</a></p>";
    echo "</div>";
    exit;
}

// --- Process POST request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($current_password) || empty($new_password) || empty($confirm_password)) {
        showMessage("กรุณากรอกข้อมูลให้ครบ");
    }

    if ($new_password !== $confirm_password) {
        showMessage("รหัสผ่านใหม่ไม่ตรงกัน");
    }

    // --- ดึงรหัสผ่านที่ถูกเข้ารหัสไว้ (Crypt-Password) ---
    $stmt = $radius_conn->prepare("SELECT value FROM radcheck WHERE username=? AND attribute='Crypt-Password'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($stored_hash);
    if (!$stmt->fetch()) {
        $stmt->close();
        showMessage("ไม่พบผู้ใช้นี้");
    }
    $stmt->close();

    // --- ตรวจสอบรหัสผ่านปัจจุบัน ---
    if (crypt($current_password, $stored_hash) !== $stored_hash) {
        showMessage("รหัสผ่านเก่าไม่ถูกต้อง");
    }

    // --- เข้ารหัสรหัสผ่านใหม่ด้วย crypt (SHA-512) ---
    $salt = '$6$' . substr(str_replace('+', '.', base64_encode(random_bytes(16))), 0, 16);
    $new_hash = crypt($new_password, $salt);

    // ใช้ transaction
    $radius_conn->begin_transaction();
    $wifi_conn->begin_transaction();

    try {
        // Update radcheck
        $stmt = $radius_conn->prepare("UPDATE radcheck SET value=? WHERE username=? AND attribute='Crypt-Password'");
        $stmt->bind_param("ss", $new_hash, $username);
        $ok1 = $stmt->execute();
        $stmt->close();

        // Update wifi_registration.users
        // ใช้ password_hash() แทน MD5 สำหรับความปลอดภัย
        $wifi_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $wifi_conn->prepare("UPDATE users SET radius_password=? WHERE radius_username=?");
        $stmt->bind_param("ss", $wifi_hash, $username);
        $ok2 = $stmt->execute();
        $stmt->close();

        if ($ok1 && $ok2) {
            $radius_conn->commit();
            $wifi_conn->commit();
            showMessage("✅ เปลี่ยนรหัสผ่านสำเร็จแล้ว", true);
        } else {
            throw new Exception("Update failed");
        }

    } catch (Exception $e) {
        $radius_conn->rollback();
        $wifi_conn->rollback();
        showMessage("❌ เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน");
    }

    $radius_conn->close();
    $wifi_conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เปลี่ยนรหัสผ่าน Wi-Fi</title>
    <link rel="stylesheet" href="wifi-style.css">
</head>
<body>
    <div class="form-container">
        <h1 class="form-title">เปลี่ยนรหัสผ่าน Wi-Fi</h1>
        <form method="post" class="form">
            <div class="form-group">
                <label>เลขบัตรประชาชน (Username)</label>
                <input type="text" name="username" required placeholder="เลขบัตรประชาชน">
            </div>
            <div class="form-group">
                <label>รหัสผ่านปัจจุบัน</label>
                <input type="password" id="current_password" name="current_password" required placeholder="รหัสผ่านปัจจุบัน">
                <input type="checkbox" id="show_current_password"> แสดงรหัสผ่าน
            </div>
            <div class="form-group">
                <label>รหัสผ่านใหม่</label>
                <input type="password" id="new_password" name="new_password" required placeholder="รหัสผ่านใหม่">
                <input type="checkbox" id="show_new_password"> แสดงรหัสผ่าน
            </div>
            <div class="form-group">
                <label>ยืนยันรหัสผ่านใหม่</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="ยืนยันรหัสผ่านใหม่">
                <input type="checkbox" id="show_confirm_password"> แสดงรหัสผ่าน
            </div>
            <div class="form-button">
                <button type="submit">เปลี่ยนรหัสผ่าน</button>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(idInput, idCheckbox) {
            const input = document.getElementById(idInput);
            const checkbox = document.getElementById(idCheckbox);
            checkbox.addEventListener('change', function() {
                input.type = this.checked ? 'text' : 'password';
            });
        }

        togglePassword('current_password', 'show_current_password');
        togglePassword('new_password', 'show_new_password');
        togglePassword('confirm_password', 'show_confirm_password');
    </script>
</body>
</html>
