<?php
// mac_address.php
// แสดง MAC Address ของเครื่อง Client (ถ้าอยู่ใน LAN เดียวกัน)

function getClientMacAddress() {
    // ตรวจสอบว่าเซิร์ฟเวอร์อนุญาตให้ใช้ shell_exec()
    if (!function_exists('shell_exec')) {
        return "ไม่สามารถใช้ shell_exec() ได้ (ฟังก์ชันนี้ถูกปิด)";
    }

    // รับ IP ของเครื่อง client
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'ไม่ทราบ IP';

    // ตรวจสอบว่าอยู่ในวง LAN หรือไม่
    if (!preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1]))/', $ipAddress)) {
        return "เครื่องนี้ไม่ได้อยู่ใน LAN เดียวกัน (IP: $ipAddress)";
    }

    // ใช้คำสั่ง arp เพื่อดึงข้อมูล MAC จาก IP
    $arp = shell_exec("arp -n " . escapeshellarg($ipAddress));
    $macAddress = null;

    // ค้นหา pattern ของ MAC address จากผลลัพธ์ arp
    if (preg_match('/([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/i', $arp, $matches)) {
        $macAddress = strtolower($matches[1]);
    }

    if ($macAddress) {
        return $macAddress;
    } else {
        return "ไม่สามารถดึง MAC Address ได้ (อาจไม่มีข้อมูลในตาราง ARP)";
    }
}

// ส่วนแสดงผล HTML
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบ MAC Address ของเครื่องลูกข่าย</title>
    <style>
        body { font-family: Tahoma, sans-serif; text-align: center; margin-top: 80px; background: #f4f6f9; }
        .box { display: inline-block; background: #fff; padding: 30px 50px; border-radius: 10px;
               box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        p { font-size: 18px; color: #555; }
        code { background: #eef; padding: 5px 8px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>MAC Address Checker</h1>
        <p><strong>IP ของคุณ:</strong> <code><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></code></p>
        <p><strong>MAC Address:</strong> <code><?php echo htmlspecialchars(getClientMacAddress()); ?></code></p>
    </div>
</body>
</html>
