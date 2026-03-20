<?php
$targetDir = __DIR__ . "/uploads/";
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

$message = "";

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["picture"])) {
    $file = $_FILES["picture"];
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $check = getimagesize($file["tmp_name"]);

    if ($check === false) {
        $message = "❌ Not an image.";
    } elseif ($file["size"] > 5 * 1024 * 1024) {
        $message = "❌ Too large (max 5MB).";
    } elseif (!in_array($imageFileType, ["jpg","jpeg","png","gif","webp"])) {
        $message = "❌ Invalid type.";
    } else {
        $safeName = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $file["name"]);
        $targetFile = $targetDir . $safeName;
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            $message = "✅ Uploaded: " . htmlspecialchars($safeName);
        } else {
            $message = "❌ Upload failed.";
        }
    }
}

// Handle delete
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete"])) {
    $file = basename($_POST["delete"]);
    $path = realpath($targetDir . $file);
    if ($path && strpos($path, realpath($targetDir)) === 0 && file_exists($path)) {
        unlink($path);
        $message = "🗑️ Deleted: " . htmlspecialchars($file);
    } else {
        $message = "❌ Cannot delete file.";
    }
}

// Collect images
$images = array_filter(scandir($targetDir), fn($f) => preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Upload & Gallery</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.msg { margin: 10px 0; font-weight: bold; }
.dropbox { border: 2px dashed #888; padding: 30px; text-align: center; margin-bottom: 20px; cursor: pointer; background: #fafafa; }
.dropbox.dragover { background: #e0f7fa; }
.gallery { display: flex; flex-wrap: wrap; gap: 20px; }
.item { text-align: center; }
.item img { width: 200px; border: 1px solid #ccc; padding: 5px; cursor: pointer; display: block; margin: 0 auto 5px; }
button.delete { background: red; color: white; border: none; padding: 5px 10px; cursor: pointer; }
button.delete:hover { background: darkred; }
</style>
</head>
<body>
<h1>📷 Upload / Paste Picture</h1>

<?php if ($message): ?><p class="msg"><?= $message ?></p><?php endif; ?>

<form id="uploadForm" method="post" enctype="multipart/form-data">
  <input type="file" id="fileInput" name="picture" hidden required>
  <div class="dropbox" id="dropbox">Click, Drag & Drop, or Paste an Image Here</div>
  <button type="submit">Upload</button>
</form>

<h2>🖼️ Gallery</h2>
<div class="gallery">
  <?php foreach ($images as $img): ?>
    <div class="item">
      <a href="view.php?img=<?= urlencode($img) ?>">
        <img src="uploads/<?= urlencode($img) ?>" alt="">
      </a>
      <form method="post" style="margin-top:5px;">
        <input type="hidden" name="delete" value="<?= htmlspecialchars($img) ?>">
        <button type="submit" class="delete">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<script>
const dropbox = document.getElementById("dropbox");
const fileInput = document.getElementById("fileInput");

dropbox.addEventListener("click", () => fileInput.click());
dropbox.addEventListener("dragover", e => { e.preventDefault(); dropbox.classList.add("dragover"); });
dropbox.addEventListener("dragleave", () => dropbox.classList.remove("dragover"));
dropbox.addEventListener("drop", e => {
  e.preventDefault(); dropbox.classList.remove("dragover");
  if (e.dataTransfer.files.length > 0) fileInput.files = e.dataTransfer.files;
});
document.addEventListener("paste", e => {
  for (const item of e.clipboardData.items) {
    if (item.type.startsWith("image/")) {
      const file = item.getAsFile();
      const dt = new DataTransfer();
      dt.items.add(file);
      fileInput.files = dt.files;
    }
  }
});
</script>
</body>
</html>
