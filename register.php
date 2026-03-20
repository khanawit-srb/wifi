<?php
// Database connection config
$host = 'localhost';
$dbname = 'radius';
$user = 'radiususer';
$pass = 'radiuspassword';

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate Thai National ID Card number function
function validateThaiID($id) {
    $id = preg_replace('/[^0-9]/', '', $id); // Remove non-digit chars
    if (strlen($id) != 13) {
        return false;
    }
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += intval($id[$i]) * (13 - $i);
    }
    $check_digit = (11 - ($sum % 11)) % 10;
    return $check_digit == intval($id[12]);
}

// Generate random password
function generatePassword($length = 8) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_card = trim($_POST['id_card']);

    if (empty($id_card)) {
        $error = "ID Card number is required.";
    } elseif (!validateThaiID($id_card)) {
        $error = "Invalid Thai National ID Card number.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM radcheck WHERE username = ?");
        $stmt->bind_param("s", $id_card);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "User with this ID Card already registered.";
        } else {
            $password = generatePassword(8);

            // Insert into radcheck
            $stmt_insert = $conn->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)");
            $stmt_insert->bind_param("ss", $id_card, $password);
            $stmt_insert->execute();

            // Insert Session-Timeout (8 hours)
            $stmt_timeout = $conn->prepare("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Session-Timeout', ':=', '28800')");
            $stmt_timeout->bind_param("s", $id_card);
            $stmt_timeout->execute();

            $success = "User registered successfully!<br>Your password: <strong>" . htmlspecialchars($password) . "</strong>";
            
            $stmt_insert->close();
            $stmt_timeout->close();
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Thai Guest WiFi Registration</title>
</head>
<body>
    <h2>Thai National ID Card Guest WiFi Registration</h2>

    <?php if (!empty($error)) : ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php elseif (!empty($success)) : ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="id_card">Thai National ID Card Number:</label><br>
        <input type="text" id="id_card" name="id_card" maxlength="13" pattern="\d{13}" title="13 digits only" required><br><br>

        <input type="submit" value="Register">
    </form>
</body>
</html>
