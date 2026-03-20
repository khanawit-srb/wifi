<?php
session_start();

/****************************************************
 * Admin Login System (Simple Secure Session)
 ****************************************************/

// 🔐 กำหนดบัญชีผู้ดูแลระบบ (ควรเก็บในฐานข้อมูลจริง)
$ADMIN_USER = "adminram";
$ADMIN_PASS = "1qaz#EDC@WSX"; // โปรดเปลี่ยนในระบบจริง

// ถ้ามีการล็อกอินอยู่แล้ว ให้ไปหน้า search_citizen.php
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: search_citizen.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim($_POST["username"] ?? "");
    $pass = trim($_POST["password"] ?? "");

    if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user;
        $_SESSION['login_time'] = time(); // timestamp สำหรับตรวจ timeout
        header("Location: search_citizen.php");
        exit;
    } else {
        $error = "❌ ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>🔐 เข้าสู่ระบบผู้ดูแลระบบ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:400px;">
    <div class="card shadow-lg">
        <div class="card-body">
            <h4 class="text-center text-primary mb-3">เข้าสู่ระบบผู้ดูแลระบบ</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label>ชื่อผู้ใช้</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
