<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แบบฟอร์มลงทะเบียน Wi-Fi มหาวิทยาลัยรามคําแหง</title>
</head>
<body>
  <h2>แบบฟอร์มลงทะเบียนขอใช้บริการอินเทอร์เน็ตไร้สาย (Wi-Fi)</h2>

  <form action="submit.php" method="post">

    <h3>1. ข้อมูลผู้ขอใช้บริการ</h3>
    ชื่อ–นามสกุล: <input type="text" name="fullname" required><br>

    ประเภทผู้ใช้: 
    <select name="user_type" required>
      <option value="บุคลากรภายใน">บุคลากรภายใน</option>
      <option value="นักศึกษา">นักศึกษา</option>
      <option value="บุคคลภายนอก">บุคคลภายนอก</option>
    </select><br>

    เลขประจําตัว (บุคลากร): <input type="text" name="staff_id"><br>
    รหัสนักศึกษา: <input type="text" name="student_id"><br>
    เลขบัตรประชาชน: <input type="text" name="citizen_id"><br>

    เบอร์โทรศัพท์: <input type="text" name="phone" required><br>
    อีเมล/เฟซบุ๊ก: <input type="text" name="email_facebook"><br>

    <h3>2. ข้อมูลอุปกรณ์</h3>
    ประเภทอุปกรณ์:
    <select name="device_type" required>
      <option value="Notebook">Notebook</option>
      <option value="Smartphone">Smartphone</option>
      <option value="Tablet">Tablet</option>
      <option value="อื่น ๆ">อื่น ๆ</option>
    </select><br>

    ยี่ห้อ / รุ่น: <input type="text" name="device_brand_model"><br>

    ระบบปฏิบัติการ:
    <select name="os" required>
      <option value="Windows">Windows</option>
      <option value="macOS">macOS</option>
      <option value="iOS">iOS</option>
      <option value="Android">Android</option>
      <option value="อื่น ๆ">อื่น ๆ</option>
    </select><br>

    หมายเลขครุภัณฑ์: <input type="text" name="asset_number"><br>

    <h3>3. รายละเอียดการขอใช้งาน</h3>
    ประเภทการเข้าใช้งาน:
    <select name="access_type" required>
      <option value="Guest Wi-Fi">Guest Wi-Fi</option>
      <option value="เครือข่ายภายในมหาวิทยาลัยฯ">เครือข่ายภายในมหาวิทยาลัยฯ</option>
    </select><br>

    วัตถุประสงค์: <textarea name="purpose"></textarea><br>
    สาขาของมหาวิทยาลัย: <input type="text" name="university_branch"><br>

    <h3>4. เงื่อนไขการใช้งาน</h3>
    <input type="checkbox" name="accept_terms" value="1" required> ยอมรับเงื่อนไขการใช้งาน<br>

    ลงชื่อผู้ขอใช้บริการ: <input type="text" name="signed_name" required><br>
    วันที่: <input type="date" name="signed_date" required><br><br>

    <input type="submit" value="ส่งแบบฟอร์ม">
  </form>
</body>
</html>
