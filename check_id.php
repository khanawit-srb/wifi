<?php
function validateThaiID($id) {
    // Remove non-digit characters (just in case)
    $id = preg_replace('/\D/', '', $id);

    // Must be 13 digits
    if (strlen($id) != 13) {
        return false;
    }

    // Calculate checksum
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += intval($id[$i]) * (13 - $i);
    }

    $check_digit = (11 - ($sum % 11)) % 10;

    return $check_digit === intval($id[12]);
}

// Process form
$result = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_card = trim($_POST["id_card"]);

    if (empty($id_card)) {
        $result = "<span style='color: red;'>Please enter an ID card number.</span>";
    } elseif (validateThaiID($id_card)) {
        $result = "<span style='color: green;'>✅ Valid Thai National ID Card Number.</span>";
    } else {
        $result = "<span style='color: red;'>❌ Invalid Thai National ID Card Number.</span>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Thai ID Card Validator</title>
</head>
<body>
    <h2>Check Thai National ID Card Validity</h2>

    <form method="POST">
        <label for="id_card">Enter 13-digit ID:</label><br>
        <input type="text" id="id_card" name="id_card" maxlength="13" pattern="\d{13}" required><br><br>
        <input type="submit" value="Validate">
    </form>

    <p><?php echo $result; ?></p>
</body>
</html>
