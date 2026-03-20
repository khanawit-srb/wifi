<?php
// get_wifi_mac.php
// Save and open on your local PC (http://localhost/get_wifi_mac.php).
// Tries multiple strategies to detect the Wi-Fi MAC for Windows, Linux, macOS.

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ---------- Utilities ---------- */

function is_shell_exec_available() {
    $disabled = ini_get('disable_functions');
    if (!$disabled) return true;
    $list = array_map('trim', explode(',', $disabled));
    return !in_array('shell_exec', $list) && !in_array('exec', $list);
}

function run_cmd($cmd) {
    // return trimmed output or empty string
    if (!is_shell_exec_available()) return '';
    $out = @shell_exec($cmd . ' 2>&1');
    if ($out === null) return '';
    return trim($out);
}

function normalize_mac($m) {
    if (!$m) return null;
    $m = trim($m);
    // Cisco style 0011.2233.4455
    if (preg_match('/^[0-9A-Fa-f]{4}\.[0-9A-Fa-f]{4}\.[0-9A-Fa-f]{4}$/', $m)) {
        $m = str_replace('.', '', $m);
        return strtolower(implode(':', str_split($m, 2)));
    }
    $m = str_replace('-', ':', $m);
    if (preg_match('/([0-9a-f]{2}(:[0-9a-f]{2}){5})/i', $m, $mm)) {
        return strtolower($mm[1]);
    }
    // raw 12 hex
    if (preg_match('/^[0-9A-Fa-f]{12}$/', $m)) {
        return strtolower(implode(':', str_split($m, 2)));
    }
    return null;
}

/* ---------- Windows Methods ---------- */

function get_wifi_mac_windows_com() {
    if (!class_exists('COM')) return null;
    try {
        $locator = new COM('WbemScripting.SWbemLocator');
        $svc = $locator->ConnectServer('.', 'root\\cimv2');
        // Query most likely active adapters (IPEnabled)
        $items = $svc->ExecQuery("SELECT Description, MACAddress, ServiceName, NetConnectionStatus FROM Win32_NetworkAdapter WHERE NetEnabled = True");
        $candidates = [];
        foreach ($items as $item) {
            $desc = (string)($item->Description ?? '');
            $mac  = (string)($item->MACAddress ?? '');
            $svcName = (string)($item->ServiceName ?? '');
            $status = (int)($item->NetConnectionStatus ?? 0);
            $isWireless = false;
            $keywords = ['wireless', 'wi-fi', 'wifi', '802.11', 'wlan', 'aironet', 'broadcom', 'atheros', 'intel'];
            foreach ($keywords as $kw) {
                if (stripos($desc, $kw) !== false || stripos($svcName, $kw) !== false) {
                    $isWireless = true; break;
                }
            }
            $candidates[] = ['desc'=>$desc,'mac'=>$mac,'wireless'=>$isWireless,'status'=>$status];
        }
        // Prefer wireless candidate with MAC
        foreach ($candidates as $c) {
            if ($c['wireless'] && !empty($c['mac'])) return normalize_mac($c['mac']);
        }
        // fallback first valid mac
        foreach ($candidates as $c) {
            if (!empty($c['mac'])) return normalize_mac($c['mac']);
        }
    } catch (Exception $e) {
        return null;
    }
    return null;
}

function get_wifi_mac_windows_netsh() {
    $out = run_cmd('netsh wlan show interfaces');
    if ($out) {
        // look for "Physical address" or "BSSID" or "MAC"
        if (preg_match('/^\s*Physical address\s*:\s*([0-9A-Fa-f:-]{17})/mi', $out, $m)) {
            return normalize_mac($m[1]);
        }
        if (preg_match('/^\s*BSSID\s*:\s*([0-9A-Fa-f:-]{17})/mi', $out, $m)) {
            return normalize_mac($m[1]);
        }
        if (preg_match('/([0-9A-Fa-f]{2}(-[0-9A-Fa-f]{2}){5})/', $out, $m)) {
            return normalize_mac($m[1]);
        }
    }
    return null;
}

function get_wifi_mac_windows_getmac() {
    $out = run_cmd('getmac /v /fo list');
    if ($out) {
        // try to find lines grouped by connection; look for "Transport Name" or "Connection Name", then MAC
        // fallback: first MAC found
        if (preg_match('/([0-9A-Fa-f]{2}(-[0-9A-Fa-f]{2}){5})/', $out, $m)) {
            return normalize_mac($m[1]);
        }
    }
    return null;
}

/* ---------- Unix (Linux/macOS) Methods ---------- */

function detect_wireless_iface_linux() {
    // try iw
    $out = run_cmd('which iw && iw dev');
    if ($out) {
        if (preg_match('/Interface\s+(\S+)/i', $out, $m)) return $m[1];
    }
    // try nmcli
    $out = run_cmd('which nmcli && nmcli -t -f DEVICE,TYPE device status');
    if ($out) {
        foreach (explode("\n", $out) as $line) {
            $parts = explode(':', trim($line));
            if (count($parts) >= 2 && trim($parts[1]) === 'wifi') return trim($parts[0]);
        }
    }
    // check /sys/class/net for wireless directory
    $ifs = run_cmd('ls /sys/class/net 2>/dev/null');
    if ($ifs) {
        foreach (explode("\n", $ifs) as $iface) {
            $iface = trim($iface);
            if ($iface === '') continue;
            if (is_dir("/sys/class/net/{$iface}/wireless")) return $iface;
        }
    }
    // ip link heuristic for names starting with w
    $out = run_cmd('ip -brief link 2>/dev/null');
    if ($out) {
        foreach (explode("\n", $out) as $line) {
            if (preg_match('/^([^\s]+)\s+/', $line, $m)) {
                $name = $m[1];
                if (preg_match('/^w/i', $name)) return $name;
            }
        }
    }
    return null;
}

function get_mac_for_iface_unix($iface) {
    if (!$iface) return null;
    // ip link
    $out = run_cmd('ip link show ' . escapeshellarg($iface) . ' 2>/dev/null');
    if ($out && preg_match('/link\/ether\s+([0-9a-f:]{17})/i', $out, $m)) return normalize_mac($m[1]);
    // ifconfig
    $out = run_cmd('ifconfig ' . escapeshellarg($iface) . ' 2>/dev/null');
    if ($out) {
        if (preg_match('/ether\s+([0-9a-f:]{17})/i', $out, $m)) return normalize_mac($m[1]);
        if (preg_match('/HWaddr\s+([0-9a-f:]{17})/i', $out, $m)) return normalize_mac($m[1]);
    }
    return null;
}

function get_wifi_mac_macos_airport() {
    $airport = '/System/Library/PrivateFrameworks/Apple80211.framework/Versions/Current/Resources/airport';
    if (is_executable($airport)) {
        $out = run_cmd($airport . ' -I');
        if ($out && preg_match('/address:\s*([0-9a-f:]{17})/mi', $out, $m)) return normalize_mac($m[1]);
    }
    return null;
}

/* ---------- Main detection ---------- */

$osFamily = PHP_OS_FAMILY ?? php_uname('s');
$detectedMac = null;
$detectedIface = null;
$debug = [];

$debug['os'] = $osFamily;
$debug['shell_exec_available'] = is_shell_exec_available() ? 'yes' : 'no';

if (stripos($osFamily, 'Windows') !== false) {
    // Try COM/WMI
    if (class_exists('COM')) {
        $debug['win_com'] = 'available';
        $mac = get_wifi_mac_windows_com();
        $debug['win_com_result'] = $mac ? $mac : 'none';
        if ($mac) { $detectedMac = $mac; }
    } else {
        $debug['win_com'] = 'not available';
    }

    // Try netsh
    if (!$detectedMac) {
        $out = run_cmd('netsh wlan show interfaces');
        $debug['netsh_output'] = $out ?: 'no output';
        $mac = get_wifi_mac_windows_netsh();
        $debug['netsh_mac'] = $mac ? $mac : 'none';
        if ($mac) $detectedMac = $mac;
    }

    // Try getmac
    if (!$detectedMac) {
        $out = run_cmd('getmac /v /fo list');
        $debug['getmac_output'] = $out ?: 'no output';
        $mac = get_wifi_mac_windows_getmac();
        $debug['getmac_mac'] = $mac ? $mac : 'none';
        if ($mac) $detectedMac = $mac;
    }

    // If still no mac, optionally show ipconfig
    if (!$detectedMac) {
        $debug['ipconfig'] = run_cmd('ipconfig /all');
    }
} else {
    // macOS or Linux
    // Try macOS airport
    $mac = get_wifi_mac_macos_airport();
    $debug['macos_airport'] = $mac ? $mac : 'none';
    if ($mac) { $detectedMac = $mac; $detectedIface = 'airport'; }

    // Try detecting wireless interface then ip link/ifconfig
    if (!$detectedMac) {
        $iface = detect_wireless_iface_linux();
        $debug['detected_iface'] = $iface ? $iface : 'none';
        if ($iface) {
            $mac = get_mac_for_iface_unix($iface);
            $debug['iface_mac'] = $mac ? $mac : 'none';
            if ($mac) { $detectedMac = $mac; $detectedIface = $iface; }
        }
    }

    // Try ip link heuristic
    if (!$detectedMac) {
        $out = run_cmd('ip -brief link 2>/dev/null');
        $debug['ip_brief'] = $out ?: 'no output';
        if ($out) {
            foreach (explode("\n", $out) as $line) {
                if (preg_match('/^([^\s]+)\s+.*\s+([0-9a-f:]{17})/i', $line, $m)) {
                    $name = $m[1]; $mac = normalize_mac($m[2]);
                    if (preg_match('/^w/i', $name)) { $detectedMac = $mac; $detectedIface = $name; break; }
                }
            }
        }
    }

    // Last resort: parse ifconfig for first "ether" with interface name starting with w
    if (!$detectedMac) {
        $out = run_cmd('ifconfig -a');
        $debug['ifconfig_all_present'] = $out ? 'yes' : 'no';
        if ($out) {
            $current = null;
            foreach (explode("\n", $out) as $line) {
                if (preg_match('/^([^\s:]+)/', $line, $m)) { $current = $m[1]; }
                if ($current && preg_match('/ether\s+([0-9a-f:]{17})/i', $line, $mm)) {
                    if (preg_match('/^w/i', $current)) { $detectedIface = $current; $detectedMac = normalize_mac($mm[1]); break; }
                    if (!$detectedMac) { $detectedMac = normalize_mac($mm[1]); $detectedIface = $current; } // first fallback
                }
            }
        }
    }
}

/* ---------- Output HTML ---------- */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Your Wi‑Fi MAC Address</title>
<style>
  body{font-family:system-ui, -apple-system, "Segoe UI", Roboto, Arial; background:#f6f8fa; padding:30px}
  .card{max-width:900px;margin:20px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.06)}
  .mac{font-size:1.6rem;color:#0b66c3;font-weight:700;letter-spacing:.12em}
  .err{color:#b30000;font-weight:700}
  pre.debug{background:#f4f6f8;padding:12px;border-radius:6px;white-space:pre-wrap;word-break:break-word}
  dl{display:grid;grid-template-columns:200px 1fr;gap:6px 12px}
  dt{font-weight:600;color:#333}
  dd{margin:0;color:#555}
</style>
</head>
<body>
  <div class="card">
    <h1>Your Wi‑Fi MAC Address</h1>

    <?php if ($detectedMac): ?>
      <p>Interface: <strong><?php echo htmlspecialchars($detectedIface ?: 'unknown'); ?></strong></p>
      <div class="mac"><?php echo htmlspecialchars($detectedMac); ?></div>
      <p style="color:#666;margin-top:10px">Detected using methods appropriate to your OS (see debug for details).</p>
    <?php else: ?>
      <p class="err">Could not detect your Wi‑Fi MAC address.</p>
      <p style="color:#555">Possible causes:</p>
      <ul>
        <li>You're not running this file on the **same machine** (must be localhost).</li>
        <li>`shell_exec()` or `exec()` is disabled in PHP configuration.</li>
        <li>Wi‑Fi is off or the wireless interface name is unusual.</li>
        <li>Required OS tools are missing (Windows: netsh/getmac; Linux: iw/ip/ifconfig; macOS: airport/networksetup).</li>
      </ul>
    <?php endif; ?>

    <hr>

    <h3>Quick checks</h3>
    <dl>
      <dt>PHP OS</dt><dd><?php echo htmlspecialchars($debug['os'] ?? php_uname()); ?></dd>
      <dt>shell_exec available</dt><dd><?php echo htmlspecialchars($debug['shell_exec_available'] ? 'yes' : 'no'); ?></dd>
      <dt>COM available (Windows)</dt><dd><?php echo class_exists('COM') ? 'yes' : 'no'; ?></dd>
    </dl>

    <h3>Debug output (trimmed)</h3>
    <p style="color:#666">This section shows outputs of commands the script attempted. Useful if you need help.</p>
    <pre class="debug"><?php
      // limit size to avoid huge dumps
      $dump = $debug;
      // include some command outputs if present
      $keys = ['netsh_output','getmac_output','ip_brief','ifconfig_all_present','ipconfig'];
      foreach ($keys as $k) {
          if (!empty($debug[$k])) {
              $dump[$k] = $debug[$k];
          }
      }
      echo htmlspecialchars(json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    ?></pre>

    <h3>If detection failed — next steps</h3>
    <ol>
      <li>Ensure you opened this file on the same PC (use <code>http://localhost/...</code>).</li>
      <li>Confirm Wi‑Fi is ON and connected.</li>
      <li>Check `phpinfo()` to find which <code>php.ini</code> is loaded and verify <code>disable_functions</code> does not list <code>shell_exec</code> or <code>exec</code>.</li>
      <li>On Windows: enable <code>php_com_dotnet</code> in php.ini to allow the COM/WMI method (optional).</li>
      <li>Run the command manually in a terminal to see raw output:
        <ul>
          <li>Windows: <code>netsh wlan show interfaces</code> and <code>getmac /v /fo list</code></li>
          <li>Linux: <code>iw dev</code>, <code>ip link show</code>, <code>ifconfig -a</code></li>
          <li>macOS: <code>/System/Library/PrivateFrameworks/Apple80211.framework/Versions/Current/Resources/airport -I</code> or <code>ifconfig</code></li>
        </ul>
      </li>
    </ol>

  </div>
</body>
</html>
