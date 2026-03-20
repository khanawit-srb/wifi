<?php
// index.php
// Shows client IP, optional ARP result (if server can run shell commands), and list of received MACs.

$jsonFile = __DIR__ . '/mac_log.json';
$records = [];
if (is_file($jsonFile)) {
    $decoded = json_decode(file_get_contents($jsonFile), true);
    if (json_last_error() === JSON_ERROR_NONE) $records = $decoded;
}

// Client IP
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Attempt ARP lookup (only works on same LAN and if shell_exec enabled)
function arp_lookup($ip) {
    if (!function_exists('shell_exec')) return null;
    $os = PHP_OS_FAMILY;
    if ($os === 'Windows') {
        // Windows: arp -a
        $out = shell_exec('arp -a ' . escapeshellarg($ip) . ' 2>&1');
    } else {
        // Linux/Unix: arp -n <ip> or ip neigh show <ip>
        // try ip neigh first
        $out = shell_exec('ip neigh show ' . escapeshellarg($ip) . ' 2>/dev/null');
        if (!$out) $out = shell_exec('arp -n ' . escapeshellarg($ip) . ' 2>/dev/null');
    }
    if (!$out) return null;
    if (preg_match('/([0-9a-f]{2}([:-])){5}[0-9a-f]{2}/i', $out, $m)) {
        return strtolower(str_replace('-', ':', $m[0]));
    }
    return null;
}

$arpMac = null;
$arpNote = null;
$privatePattern = '/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1]))/';
if (preg_match($privatePattern, $clientIp)) {
    $arpMac = arp_lookup($clientIp);
    if ($arpMac === null) $arpNote = 'ARP lookup unavailable (shell_exec disabled or no ARP entry).';
} else {
    $arpNote = 'Client not in private LAN range — ARP lookup skipped.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MAC receiver</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:900px;margin:24px auto;padding:0 12px}
header{margin-bottom:18px}
table{border-collapse:collapse;width:100%;margin-top:12px}
th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:14px}
th{background:#f6f6f6}
.notice{background:#fffbdd;padding:10px;border-left:4px solid #f1c40f;margin:8px 0}
.ok{background:#e9f9ef;padding:10px;border-left:4px solid #27ae60;margin:8px 0}
</style>
</head>
<body>
<header>
<h1>MAC Receiver</h1>
<p>Your public request IP (as seen by server): <strong><?php echo htmlspecialchars($clientIp); ?></strong></p>
<?php if ($arpMac): ?>
    <div class="ok">ARP lookup (same LAN): MAC = <strong><?php echo htmlspecialchars($arpMac); ?></strong></div>
<?php else: ?>
    <div class="notice"><?php echo htmlspecialchars($arpNote ?? 'ARP lookup not performed.'); ?></div>
<?php endif; ?>
<p>To report your MAC to this server, run one of the client scripts (PowerShell / bash) and point it to <code><?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/receive_mac.php'); ?></code></p>
</header>

<section>
<h2>Received MACs</h2>
<?php if (empty($records)): ?>
    <p>No MACs received yet.</p>
<?php else: ?>
    <table>
        <thead><tr><th>#</th><th>Timestamp (UTC)</th><th>IP</th><th>MAC</th><th>Label</th><th>User Agent</th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($records) as $i => $r): ?>
            <tr>
                <td><?php echo count($records) - $i; ?></td>
                <td><?php echo htmlspecialchars($r['timestamp'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['ip'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['mac'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['label'] ?? '-'); ?></td>
                <td style="max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($r['user_agent'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</section>

</body>
</html>
