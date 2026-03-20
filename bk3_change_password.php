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
    echo "<p><a href='change_password.php' style='color:blue;text-decoration:underline;'>กลับไปยังหน้าฟอร์ม</a></p>";
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

    // --- Check current password (radius.radcheck) ---
    $stmt = $radius_conn->prepare("SELECT value FROM radcheck WHERE username=? AND attribute='Cleartext-Password'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    if (!$stmt->fetch()) {
        $stmt->close();
        showMessage("ไม่พบผู้ใช้นี้");
    }
    $stmt->close();

    if ($current_password !== $stored_password) {
        showMessage("รหัสผ่านเก่าไม่ถูกต้อง");
    }

    // ใช้ transaction กันข้อมูลไม่ตรงกัน
    $radius_conn->begin_transaction();
    $wifi_conn->begin_transaction();

    try {
        // --- Update radcheck ---
        $stmt = $radius_conn->prepare("UPDATE radcheck SET value=? WHERE username=? AND attribute='Cleartext-Password'");
        $stmt->bind_param("ss", $new_password, $username);
        $ok1 = $stmt->execute();
        $stmt->close();

        // --- Update wifi_registration.users ---
        $stmt = $wifi_conn->prepare("UPDATE users SET radius_password=? WHERE radius_username=?");
        $stmt->bind_param("ss", $new_password, $username);
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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 py-10">
<div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-xl font-semibold mb-4 text-center">เปลี่ยนรหัสผ่าน Wi-Fi</h1>
    <form method="post" class="space-y-4">
        <label class="block">
            เลขบัตรประชาชน (Username)
            <input type="text" name="username" required class="mt-1 w-full border rounded-md px-3 py-2" placeholder="เลขบัตรประชาชน">
        </label>
        <label class="block">
            รหัสผ่านปัจจุบัน
            <input type="password" name="current_password" required class="mt-1 w-full border rounded-md px-3 py-2" placeholder="รหัสผ่านปัจจุบัน">
        </label>
        <label class="block">
            รหัสผ่านใหม่
            <input type="password" name="new_password" required class="mt-1 w-full border rounded-md px-3 py-2" placeholder="รหัสผ่านใหม่">
        </label>
        <label class="block">
            ยืนยันรหัสผ่านใหม่
            <input type="password" name="confirm_password" required class="mt-1 w-full border rounded-md px-3 py-2" placeholder="ยืนยันรหัสผ่านใหม่">
        </label>
        <div class="text-center">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">เปลี่ยนรหัสผ่าน</button>
        </div>
    </form>
</div>
</body>
</html>
