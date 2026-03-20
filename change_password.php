<?php
include_once('app.php');

session_start();

$campus_code = $_SESSION['campus_code'];
$campus_type = $_SESSION['campus_type'];

// --- Database configs ---
$radius_cfg = ["host"=>"localhost","user"=>"root","pass"=>"","db"=>"radius"];
$wifi_cfg   = ["host"=>"localhost","user"=>"root","pass"=>"","db"=>"wifi_registration"];
// $radius_cfg = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"radius"];
// $wifi_cfg   = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"wifi_registration"];

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

showMessage("✅ เปลี่ยนรหัสผ่านสำเร็จแล้ว", true);

// --- Function to show message ---
function showMessage($message, $success = false) {
    global $campus_wifi, $campus_lan, $campus_code, $campus_type;

    $color = $success ? "green" : "red";

    $url_login = ($campus_type == "WIFI") ? $campus_wifi[$campus_code] : $campus_lan[$campus_code];

    echo "<div style='font-family:sans-serif;padding:20px;border:1px solid $color;border-radius:10px;max-width:400px;margin:20px auto;text-align:center;'>";
    echo "<p style='color:$color;font-size:18px;'>$message</p>";
    
    echo "<div style='display: flex;align-items: center;justify-content: space-evenly;'>";
        echo "<p><a href='index.php' style='color:blue;text-decoration:underline;'>กลับไปยังหน้าฟอร์ม</a></p>";
        if($campus_code != ""){
            echo "<a href=' $url_login ' target='_blank' style='color:blue;text-decoration:underline;'>เข้าสู่ระบบ</a>";
        }
    echo "</div>";
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
    $check_hash = crypt($current_password, $stored_hash);
    if ($check_hash !== $stored_hash) {
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
        $stmt = $wifi_conn->prepare("UPDATE users SET radius_password=? WHERE radius_username=?");
        $stmt->bind_param("ss", md5($new_password), $username);
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
    <style>
        .toggle-password {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 65%;
            transform: translateY(-50%);
            color: #007bff;
            font-size: 14px;
            user-select: none;
        }
        .password-wrapper {
            position: relative;
        }
        .form-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background-color: #fff;
            font-family: "Segoe UI", sans-serif;
        }
        .form-title {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .form-group input {
            /* width: 100%; */
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .form-button {
            text-align: center;
        }
        .form-button button {
            padding: 10px 20px;
            border: none;
            background-color: #007bff;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        .form-button button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1 class="form-title">เปลี่ยนรหัสผ่าน Wi-Fi</h1>
        <form method="post" class="form">
            <div class="form-group">
                <label>เลขบัตรประชาชน (Username)</label>
                <input type="text" name="username" required placeholder="เลขบัตรประชาชน">
            </div>
            <div class="form-group password-wrapper">
                <label>รหัสผ่านปัจจุบัน</label>
                <input type="password" name="current_password" id="current_password" required placeholder="รหัสผ่านปัจจุบัน">
                <span class="toggle-password" onclick="togglePassword('current_password', this)">แสดง</span>
            </div>
            <div class="form-group password-wrapper">
                <label>รหัสผ่านใหม่</label>
                <input type="password" name="new_password" id="new_password" required placeholder="รหัสผ่านใหม่">
                <span class="toggle-password" onclick="togglePassword('new_password', this)">แสดง</span>
            </div>
            <div class="form-group password-wrapper">
                <label>ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="ยืนยันรหัสผ่านใหม่">
                <span class="toggle-password" onclick="togglePassword('confirm_password', this)">แสดง</span>
            </div>
            <div class="form-button">
                <button type="submit">เปลี่ยนรหัสผ่าน</button>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(fieldId, element) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
                element.textContent = "ซ่อน";
            } else {
                field.type = "password";
                element.textContent = "แสดง";
            }
        }
    </script>
</body>
</html>
