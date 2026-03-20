<?php 

session_start(); 

if(empty($_SESSION)){
    header("Location: forgot_password.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบฟอร์มลงทะเบียน Wi-Fi มหาวิทยาลัยรามคำแหง</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="wifi-otp-form.css">
</head>

<body>

<section class="otp-area">
    <div class="container-otp-box">
        <div class="otp-card">
            <form action="verify_otp_forgot_password.php" method="post">
                <div class="otp-header">
                    <div class="title">
                        <h1>แบบฟอร์มลงทะเบียนขอใช้บริการอินเทอร์เน็ตไร้สาย (Wi-Fi)</h1>
                        <span>มหาวิทยาลัยรามคำแหง</span>
                    </div>
                    
                </div>
                <div class="otp-content">
                    <div class="text-center mb-10">
                        <h2>ยืนยันตัวตนผ่านเบอร์มือถือ (OTP)</h2>
                    </div>

                    <div class="text-center mb-10">
                        <span class="remark"><b>กรุณากรอกรหัส OTP ที่ได้รับทาง SMS </b></span></br>
                        <span class="remark"><b>ผ่านหมายเลขเบอร์มือถือ : <span class="text-green"><?php echo $_SESSION['wifi_forgot_password_form']['phone']; ?></span></b></span></br>
                        <span class="remark"><b>เลขที่อ้างอิง : <span class="otp-reference text-green"><?php echo $_SESSION['wifi_forgot_password_form']['otp']['ref_code']; ?></span></b></span>
                    </div>

                    <div class="otp-input mb-10">
                        <input type="text" class="input-otp-password" id="otp_code" name="otp_code">
                    </div>

                    <div class="text-center mb-10">
                        <span class="sub-remark">กรุณายืนยันรหัส OTP ภายใน 5 นาที หลังจากได้รับรหัส</span></br>
                        <span class="sub-remark">หากเกินเวลาที่กำหนด ระบบจะยกเลิกรายการอัตโนมัติ</span></br>
                        <span class="sub-remark">และท่านจะต้องทำรายการใหม่อีกครั้ง</span></br>
                    </div>
                </div>
                <div class="otp-footer">
                    <div class="text-left">
                        <a href="forgot_password.php" class="btn btn-secondary">ย้อนกลับ</a>
                    </div>
                    <div class="text-right">
                        <input type="hidden" id="ref_code" name="ref_code" value="<?php echo $_SESSION['wifi_forgot_password_form']['otp']['ref_code']; ?>">
                        <button type="submit" class="btn-confirm-otp" name="btn-confirm-otp">ยืนยัน</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

</body>
</html>