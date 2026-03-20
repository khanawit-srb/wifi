<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แบบฟอร์มลงทะเบียน Wi-Fi มหาวิทยาลัยรามคำแหง</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 py-10">
  <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 border-b">
      <h1 class="text-2xl font-semibold text-center">แบบฟอร์มลงทะเบียนขอใช้บริการอินเทอร์เน็ตไร้สาย (Wi-Fi)</h1>
      <p class="text-center text-sm text-gray-600 mt-1">มหาวิทยาลัยรามคำแหง</p>
    </div>

    <form action="submit.php" method="post" class="p-6 space-y-6">

      <!-- 1. ข้อมูลผู้ขอใช้บริการ -->
      <section>
        <h2 class="font-medium mb-2">1. ข้อมูลผู้ขอใช้บริการ</h2>
        <label class="block">
          ชื่อ–นามสกุล
          <input name="fullname" required class="mt-1 w-full border rounded-md px-3 py-2" placeholder="ชื่อ–นามสกุล">
        </label>

        <label class="block mt-3">
          ประเภทผู้ใช้
          <select name="user_type" required class="mt-1 w-full border rounded-md px-3 py-2">
            <option value="">-- เลือกประเภท --</option>
            <option value="บุคลากรภายใน">บุคลากรภายใน</option>
            <option value="นักศึกษา">นักศึกษา</option>
            <option value="บุคคลภายนอก">บุคคลภายนอก</option>
          </select>
        </label>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
          <input type="text" name="staff_id" class="border rounded-md px-3 py-2" placeholder="เลขประจำตัวบุคลากร">
          <input type="text" name="student_id" class="border rounded-md px-3 py-2" placeholder="รหัสนักศึกษา">
          <input type="text" name="citizen_id" class="border rounded-md px-3 py-2" placeholder="เลขบัตรประชาชน">
        </div>

        <label class="block mt-3">
          เบอร์โทรศัพท์
          <input type="text" name="phone" required class="mt-1 w-full border rounded-md px-3 py-2" placeholder="เช่น 0812345678">
        </label>

        <label class="block mt-3">
          อีเมล/เฟซบุ๊ก
          <input type="text" name="email_facebook" class="mt-1 w-full border rounded-md px-3 py-2" placeholder="อีเมลหรือ Facebook">
        </label>
      </section>

      <!-- 2. ข้อมูลอุปกรณ์ -->
      <section>
        <h2 class="font-medium mb-2">2. ข้อมูลอุปกรณ์</h2>
        <label class="block">
          ประเภทอุปกรณ์
          <select name="device_type" required class="mt-1 w-full border rounded-md px-3 py-2">
            <option value="Notebook">Notebook</option>
            <option value="Smartphone">Smartphone</option>
            <option value="Tablet">Tablet</option>
            <option value="อื่น ๆ">อื่น ๆ</option>
          </select>
        </label>

        <label class="block mt-3">
          ยี่ห้อ / รุ่น
          <input type="text" name="device_brand_model" class="mt-1 w-full border rounded-md px-3 py-2">
        </label>

        <label class="block mt-3">
          ระบบปฏิบัติการ
          <select name="os" required class="mt-1 w-full border rounded-md px-3 py-2">
            <option value="Windows">Windows</option>
            <option value="macOS">macOS</option>
            <option value="iOS">iOS</option>
            <option value="Android">Android</option>
            <option value="อื่น ๆ">อื่น ๆ</option>
          </select>
        </label>

        <label class="block mt-3">
          หมายเลขครุภัณฑ์
          <input type="text" name="asset_number" class="mt-1 w-full border rounded-md px-3 py-2">
        </label>
      </section>

      <!-- 3. รายละเอียดการขอใช้งาน -->
      <section>
        <h2 class="font-medium mb-2">3. รายละเอียดการขอใช้งาน</h2>
        <label class="block">
          ประเภทการเข้าใช้งาน
          <select name="access_type" required class="mt-1 w-full border rounded-md px-3 py-2">
            <option value="Guest Wi-Fi">Guest Wi-Fi</option>
            <option value="เครือข่ายภายในมหาวิทยาลัยฯ">เครือข่ายภายในมหาวิทยาลัยฯ</option>
          </select>
        </label>

        <label class="block mt-3">
          วัตถุประสงค์
          <textarea name="purpose" class="mt-1 w-full border rounded-md px-3 py-2"></textarea>
        </label>

        <label class="block mt-3">
          สาขาของมหาวิทยาลัย
          <input type="text" name="university_branch" class="mt-1 w-full border rounded-md px-3 py-2">
        </label>
      </section>

      <!-- 4. เงื่อนไขการใช้งาน -->
      <section>
        <h2 class="font-medium mb-2">4. เงื่อนไขการใช้งาน</h2>
        <div class="bg-gray-50 border rounded-md p-4 text-sm space-y-2">
          <p>ผู้ใช้ต้องยอมรับก่อนการอนุมัติให้เข้าใช้งาน</p>
          <ol class="list-decimal list-inside text-sm">
            <li>ห้ามกระทำการใด ๆ ที่ผิดกฎหมายหรือรบกวนผู้อื่น</li>
            <li>มหาวิทยาลัยฯ มีสิทธิ์เก็บบันทึกการใช้งานตามกฎหมาย</li>
            <li>ผู้ใช้ต้องเก็บรักษาบัญชีผู้ใช้ให้ปลอดภัย</li>
            <li>หากฝ่าฝืน มหาวิทยาลัยฯ อาจระงับการใช้งานทันที</li>
          </ol>
        </div>

        <label class="flex items-center gap-2 mt-3">
          <input type="checkbox" name="accept_terms" value="1" required>
          ข้าพเจ้ายอมรับเงื่อนไขการใช้งาน
        </label>

        <div class="grid grid-cols-2 gap-3 mt-3">
          <input type="text" name="signed_name" required class="border rounded-md px-3 py-2" placeholder="ลงชื่อผู้ขอใช้บริการ">
          <input type="date" name="signed_date" required class="border rounded-md px-3 py-2">
        </div>
      </section>

      <div class="flex justify-end gap-3 pt-4 border-t">
        <input type="submit" value="ส่งแบบฟอร์ม" class="px-4 py-2 bg-blue-600 text-white rounded-md">
      </div>
    </form>
  </div>
</body>
</html>
