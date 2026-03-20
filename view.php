<?php
// Debug mode: show PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

$targetDir = __DIR__ . "/uploads/";
$file = $_GET['img'] ?? '';
$path = realpath($targetDir . $file);

if (!$file || !$path || strpos($path, realpath($targetDir)) !== 0 || !file_exists($path)) {
    die("❌ Image not found.");
}

// Collect gallery images
$images = array_values(array_filter(scandir($targetDir), fn($f) => preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f)));
$currentIndex = array_search($file, $images);
$prev = $currentIndex > 0 ? $images[$currentIndex - 1] : null;
$next = $currentIndex < count($images) - 1 ? $images[$currentIndex + 1] : null;

// Rotate function
function rotateImage($filePath, $degrees) {
    $info = getimagesize($filePath);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($filePath); break;
        case 'image/png':  $src = imagecreatefrompng($filePath); break;
        case 'image/gif':  $src = imagecreatefromgif($filePath); break;
        case 'image/webp': $src = imagecreatefromwebp($filePath); break;
        default: return false;
    }

    $rotated = imagerotate($src, $degrees, 0);

    if ($mime === 'image/jpeg') imagejpeg($rotated, $filePath, 90);
    elseif ($mime === 'image/png') imagepng($rotated, $filePath);
    elseif ($mime === 'image/gif') imagegif($rotated, $filePath);
    elseif ($mime === 'image/webp') imagewebp($rotated, $filePath, 90);

    imagedestroy($src);
    imagedestroy($rotated);
    return true;
}

// Handle delete
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete"])) {
    unlink($path);
    header("Location: upload.php");
    exit;
}

// Handle rotate
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["rotate"])) {
    $degrees = ($_POST["rotate"] === "left") ? 90 : -90;
    if (rotateImage($path, $degrees)) {
        // Add timestamp so browser doesn’t load cached old image
        header("Location: view.php?img=" . urlencode($file) . "&t=" . time());
        exit;
    } else {
        die("❌ Rotation failed. Check if GD is enabled in PHP.");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>View Image</title>
<style>
body { font-family: Arial, sans-serif; text-align: center; margin: 20px; }
img { max-width: 90%; height: auto; border: 2px solid #444; margin-bottom: 20px; }
.buttons { margin-top: 15px; }
button { margin: 5px; padding: 10px 20px; font-size: 16px; cursor: pointer; }
.delete { background: red; color: white; border: none; }
.delete:hover { background: darkred; }
</style>
</head>
<body>
<h1>🖼️ Viewing Picture</h1>
<img src="uploads/<?= urlencode($file) ?>?t=<?= time() ?>" alt="">

<div class="buttons">
    <?php if ($prev): ?>
        <a id="prevLink" href="view.php?img=<?= urlencode($prev) ?>"><button>⬅️ Previous</button></a>
    <?php endif; ?>
    <?php if ($next): ?>
        <a id="nextLink" href="view.php?img=<?= urlencode($next) ?>"><button>Next ➡️</button></a>
    <?php endif; ?>
</div>

<div class="buttons">
    <form id="rotateLeft" method="post" style="display:inline;">
        <input type="hidden" name="rotate" value="left">
        <button type="submit">↩️ Rotate Left</button>
    </form>
    <form id="rotateRight" method="post" style="display:inline;">
        <input type="hidden" name="rotate" value="right">
        <button type="submit">↪️ Rotate Right</button>
    </form>
</div>

<div class="buttons">
    <form id="deleteForm" method="post" style="display:inline;">
        <input type="hidden" name="delete" value="<?= htmlspecialchars($file) ?>">
        <button type="submit" class="delete">🗑️ Delete</button>
    </form>
    <a id="backLink" href="upload.php"><button>⬅️ Back to Gallery</button></a>
</div>

<script>
document.addEventListener("keydown", function(e) {
    if (e.key === "ArrowLeft") {
        const prev = document.getElementById("prevLink");
        if (prev) window.location = prev.href;
    } else if (e.key === "ArrowRight") {
        const next = document.getElementById("nextLink");
        if (next) window.location = next.href;
    } else if (e.key === "Escape") {
        document.getElementById("backLink").click();
    } else if (e.key === "Delete") {
        if (confirm("Delete this picture?")) {
            document.getElementById("deleteForm").submit();
        }
    } else if (e.key === "r") {
        document.getElementById("rotateRight").submit(); // "R" key = rotate right
    } else if (e.key === "l") {
        document.getElementById("rotateLeft").submit(); // "L" key = rotate left
    }
});
</script>
</body>
</html>
