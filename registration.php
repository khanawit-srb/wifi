<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบฟอร์มลงทะเบียน Wi-Fi มหาวิทยาลัยรามคำแหง</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="wifi-form.css">
</head>
<body>

    <div class="form-container">
        <div class="form-header">
            <h1>แบบฟอร์มลงทะเบียนขอใช้บริการอินเทอร์เน็ตไร้สาย (Wi-Fi)</h1>
            <p>มหาวิทยาลัยรามคำแหง</p>
        </div>

        <form action="submit.php" method="post" class="form-body">

            <!-- 1. ข้อมูลผู้ขอใช้บริการ -->
            <section class="form-section">
                <h2>1. ข้อมูลผู้ขอใช้บริการ</h2>

                <label>
                    ชื่อ–นามสกุล
                    <input name="fullname" type="text" required placeholder="ชื่อ–นามสกุล">
                </label>

                <label>
                    เลขบัตรประชาชน
                    <input name="citizen_id" type="text" required placeholder="เลขบัตรประชาชน">
                </label>

                <label>
                    เบอร์มือถือ
                    <input name="phone" type="text" required placeholder="">
                </label>

                <label>
                    อีเมล
                    <input name="email" type="email" placeholder="อีเมล">
                </label>
            </section>

            <!-- 2. เงื่อนไขการใช้งาน -->
            <section class="form-section">
                <h2>2. เงื่อนไขการใช้งาน</h2>
                <div class="terms-box">
                    <p>ผู้ใช้งานต้องยอมรับก่อนการอนุมัติให้เข้าใช้</p>
                    <ol>
                        <li>ต้องให้ข้อมูลส่วนบุคคลที่เป็นจริง ถูกต้อง และเป็นปัจจุบัน</li>
                        <li>ห้ามกระทำการใดๆ ที่ผิดกฎหมายหรือรบกวนผู้อื่น</li>
                        <li>ผู้ใช้งานต้องใช้บัญชีของตนเองเท่านั้น ห้ามใช้บัญชีของผู้อื่น</li>
                        <li>หากฝ่าฝืน มหาวิทยาลัยฯ มีสิทธิระงับหรือยกเลิกการใช้งานโดยไม่ต้องแจ้งล่วงหน้า</li>
                    </ol>
                </div>

                <label class="checkbox-label">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    ข้าพเจ้ารับทราบและยอมรับเงื่อนไขการใช้งาน รวมถึงการบันทึกข้อมูลจราจรคอมพิวเตอร์ตาม พ.ร.บ. ว่าด้วยการกระทำความผิดเกี่ยวกับคอมพิวเตอร์ พ.ศ. 2560 และการประมวลผลข้อมูลส่วนบุคคลตาม PDPA
                </label>
            </section>

            <div class="form-footer">
                <input type="submit" value="ส่งแบบฟอร์ม" class="submit-button">
            </div>

        </form>
    </div>

</body>
</html>
