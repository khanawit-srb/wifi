<?php
session_start();

// ✅ ตรวจสอบการล็อกอินและ Timeout (10 นาที)
$timeout_duration = 600; // 600 วินาที = 10 นาที
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_admin.php");
    exit;
}
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login_admin.php?timeout=1");
    exit;
}
$_SESSION['login_time'] = time(); // refresh session activity

/****************************************************
 * Search & Delete Citizen Record (ปลอดภัยด้วย Prepared Statement)
 ****************************************************/
$host = "localhost";
$user = "root";
$password = "1qaz#EDC";
$dbname = "wifi_registration";

//update 2026-02-17
$radius_db_hostname = "localhost";
$radius_db_username = "root";
$radius_db_password = "1qaz#EDC";
$radius_db_name   = "radius";

// สร้างการเชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("<h3 style='color:red'>❌ Connection failed: " . $conn->connect_error . "</h3>");
}

//update 2026-02-17
// สร้างการเชื่อมต่อฐานข้อมูล radius
$conn_radius = new mysqli($radius_db_hostname, $radius_db_username, $radius_db_password, $radius_db_name);
if ($conn_radius->connect_error) {
    die("<h3 style='color:red'>❌ Connection failed: " . $conn_radius->connect_error . "</h3>");
}

// ------------------------
// การลบข้อมูล (ถ้ามีคำสั่ง delete)
// ------------------------
if (isset($_GET['delete'])) {
    $del_id = trim($_GET['delete']);
    if ($del_id !== "") {
        $del_stmt = $conn->prepare("DELETE FROM users WHERE citizen_id = ?");
        $del_stmt->bind_param("s", $del_id);
        if ($del_stmt->execute()) {
            echo "<div class='alert alert-success text-center m-3'>
                    ✅ ลบข้อมูลของหมายเลขบัตร <strong>$del_id</strong> สำเร็จแล้ว
                  </div>";
        } else {
            echo "<div class='alert alert-danger text-center m-3'>
                    ❌ เกิดข้อผิดพลาดในการลบข้อมูล: " . htmlspecialchars($conn->error) . "
                  </div>";
        }
        $del_stmt->close();

        //update 2026-02-17
        $del_radius_stmt = $conn_radius->prepare("DELETE FROM radcheck WHERE username = ?");
        $del_radius_stmt->bind_param("s", $del_id);
        $del_radius_stmt->execute();
        $del_radius_stmt->close();
        
    }
}

// รับค่าจากฟอร์มค้นหา
$citizen_id = trim($_GET['citizen_id'] ?? "");
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>🔍 ค้นหาข้อมูลผู้ใช้ด้วยเลขบัตรประชาชน</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
function confirmDelete(citizenId) {
    if (confirm("คุณต้องการลบข้อมูลของบัตรประชาชน " + citizenId + " ใช่หรือไม่?")) {
        window.location.href = "search_citizen.php?delete=" + citizenId;
    }
}
</script>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h2 class="text-primary">🔍 ค้นหาข้อมูลผู้ใช้ด้วยเลขบัตรประชาชน</h2>
        <div>
            <span class="me-3 text-secondary">👤 แอดมิน: <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
            <a href="logout_admin.php" class="btn btn-outline-danger btn-sm">🚪 ออกจากระบบ</a>
        </div>
    </div>

    <!-- ฟอร์มค้นหา -->
    <form class="row g-3 mb-4" method="get" action="">
        <div class="col-md-6">
            <input type="text" name="citizen_id" value="<?= htmlspecialchars($citizen_id) ?>" class="form-control" placeholder="กรอกเลขบัตรประชาชน 13 หลัก..." required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
        </div>
        <div class="col-md-2">
            <a href="search_citizen.php" class="btn btn-secondary w-100">ล้างค่า</a>
        </div>
    </form>

<?php
// ------------------------
// ส่วนแสดงผลการค้นหา
// ------------------------
if ($citizen_id !== "") {
    $stmt = $conn->prepare("SELECT * FROM users WHERE citizen_id = ?");
    $stmt->bind_param("s", $citizen_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h5>ผลการค้นหา:</h5>";

    if ($result->num_rows > 0) {
        echo "<table class='table table-bordered table-striped align-middle'>";
        echo "<thead class='table-dark'><tr>";
        while ($field = $result->fetch_field()) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "<th class='text-center'>การจัดการ</th>";
        echo "</tr></thead><tbody>";

        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "<td class='text-center'>
                    <button class='btn btn-danger btn-sm' onclick='confirmDelete(\"" . htmlspecialchars($row['citizen_id']) . "\")'>
                        🗑️ ลบข้อมูล
                    </button>
                  </td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-warning mt-3'>ไม่พบข้อมูลเลขบัตร <strong>" . htmlspecialchars($citizen_id) . "</strong></div>";
    }

    $stmt->close();
}
$conn->close();
?>
</div>
</body>
</html>
