<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบลงทะเบียน Wi-Fi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; background-color: #f8f9fa; }
        .card { width: 100%; max-width: 400px; }
    </style>
</head>
<body>
    <div class="card text-center shadow-sm">
        <div class="card-header">
            <h3>ยินดีต้อนรับสู่ระบบ Wi-Fi</h3>
        </div>
        <div class="card-body">
            <p class="card-text">กรุณาเลือกรายการที่ต้องการ</p>
            <a href="registration.php" class="btn btn-primary btn-lg w-100 mb-3">ลงทะเบียนใช้งานใหม่</a>
            <a href="change_password.php" class="btn btn-secondary btn-lg w-100 mb-3">เปลี่ยนรหัสผ่าน</a>
            <a href="forgot_password.php" class="btn btn-warning btn-lg w-100">ลืมรหัสผ่าน</a>
        </div>
    </div>
</body>
</html>
