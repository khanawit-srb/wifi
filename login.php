<?php
session_start();

// --- Handle logout ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// --- Database configs ---
$radius_cfg = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"radius"];
$wifi_cfg   = ["host"=>"localhost","user"=>"root","pass"=>"1qaz#EDC","db"=>"wifi_registration"];

// --- Connect radius DB ---
$radius_conn = new mysqli($radius_cfg["host"], $radius_cfg["user"], $radius_cfg["pass"], $radius_cfg["db"]);
if ($radius_conn->connect_error) {
    die("Radius DB connection failed: " . $radius_conn->connect_error);
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $auth_ok = false;

    // --- 1. Try Cleartext-Password ---
    $stmt = $radius_conn->prepare("SELECT value FROM radcheck WHERE username=? AND attribute='Cleartext-Password' LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($stored_clear);
    $stmt->fetch();
    $stmt->close();

    if (!empty($stored_clear) && hash_equals($stored_clear, $password)) {
        $auth_ok = true;
    } else {
        // --- 2. Try Crypt-Password ---
        $stmt = $radius_conn->prepare("SELECT value FROM radcheck WHERE username=? AND attribute='Crypt-Password' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($stored_hash);
        $stmt->fetch();
        $stmt->close();

        if (!empty($stored_hash) && crypt($password, $stored_hash) === $stored_hash) {
            $auth_ok = true;
        }
    }

    // --- 3. Check password expiry ---
    if ($auth_ok) {
        $stmt = $radius_conn->prepare("SELECT value FROM radcheck WHERE username=? AND attribute='Password-Expire' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($expire_value);
        $stmt->fetch();
        $stmt->close();

        if (!empty($expire_value)) {
            $expire_time = is_numeric($expire_value) ? (int)$expire_value : strtotime($expire_value);
            if ($expire_time !== false && time() > $expire_time) {
                $auth_ok = false;
                $message = "รหัสผ่านหมดอายุแล้ว กรุณาเปลี่ยนรหัสผ่านใหม่";
            }
        }
    }

    if ($auth_ok) {
        $_SESSION['username'] = $username;
        $message = "✅ เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับ $username";
    } else if (!$message) {
        $message = "❌ ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ Wi-Fi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card p-4 shadow-sm">
                <h4 class="card-title text-center mb-4">เข้าสู่ระบบ Wi-Fi</h4>

                <?php if ($message): ?>
                    <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if (!isset($_SESSION['username'])): ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                </form>
                <?php else: ?>
                    <div class="text-center">
                        <p>ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
                        <a href="change_password.php" class="btn btn-warning w-100 mb-2">เปลี่ยนรหัสผ่าน</a>
                        <a href="?logout=1" class="btn btn-danger w-100">ออกจากระบบ</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
</body>
</html>
