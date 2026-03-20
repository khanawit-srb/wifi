<?php
$filename = "notes.txt";

// Save new note
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Add new note
    if (isset($_POST["note"]) && !empty(trim($_POST["note"]))) {
        $note = trim($_POST["note"]);
        $note = htmlspecialchars($note); // prevent XSS
        file_put_contents($filename, $note . PHP_EOL, FILE_APPEND);
    }

    // Delete a note
    if (isset($_POST["delete"])) {
        $indexToDelete = intval($_POST["delete"]);
        if (file_exists($filename)) {
            $lines = file($filename, FILE_IGNORE_NEW_LINES);
            if (isset($lines[$indexToDelete])) {
                unset($lines[$indexToDelete]);
                file_put_contents($filename, implode(PHP_EOL, $lines) . PHP_EOL);
            }
        }
    }
}

// Make URLs clickable
function make_links_clickable($text) {
    $text = htmlspecialchars($text); // Ensure safety before regex
    $url_pattern = '/(https?:\/\/[^\s]+)/i';
    return preg_replace_callback($url_pattern, function ($matches) {
        $url = $matches[0];
        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>';
    }, $text);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Notes with Links & Delete</title>
</head>
<body>
    <h1>Write a Note</h1>
    <form method="POST" action="">
        <textarea name="note" rows="5" cols="50" placeholder="Write your note here..."></textarea><br><br>
        <input type="submit" value="Save Note">
    </form>

    <h2>Saved Notes:</h2>
    <ul style="list-style-type: none; padding: 0;">
<?php
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $index => $line) {
            echo "<li style='margin-bottom: 10px;'>";
            echo make_links_clickable($line);
            echo "
                <form method='POST' action='' style='display:inline; margin-left:10px;'>
                    <input type='hidden' name='delete' value='" . $index . "'>
                    <input type='submit' value='Delete'>
                </form>
            ";
            echo "</li>";
        }
    } else {
        echo "<li>No notes yet.</li>";
    }
?>
    </ul>
</body>
</html>
