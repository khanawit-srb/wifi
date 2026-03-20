<?php 
    session_start(); 

    session_destroy();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ลืมรหัสผ่าน Wi-Fi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="wifi-forgot-password.css">
</head>

<body>

<section class="forgot-password-area">
    <div class="container-forgot-password-box">
        <div class="forgot-password-card">
            <form action="save_forgot_password.php" method="post">
                <div class="forgot-password-header">
                    <div class="title">
                        <h1>ลืมรหัสผ่าน Wi-Fi</h1>
                        <span>มหาวิทยาลัยรามคำแหง</span>
                    </div>
                </div>
                <div class="forgot-password-content">
                    <div class="forgot-password-input mb-10">
                        <div class="form-group">
                            <label>เลขบัตรประชาชน หรือ อีเมล</label>
                            <input type="text" class="input-forgot-password" id="identifier" name="identifier" required placeholder="เลขบัตรประชาชน หรือ อีเมล">
                        </div>
                    </div>
                </div>
                <div class="forgot-password-footer">
                    <div class="text-left">
                        <a href="index.php" class="btn btn-secondary">ย้อนกลับ</a>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn-forgot-passord" name="btn-forgot-passord">รีเซ็ตรหัสผ่าน</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

</body>
</html>