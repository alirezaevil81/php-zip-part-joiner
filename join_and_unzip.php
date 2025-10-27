<?php
// join_and_unzip.php — clean English version
$ACCESS_KEY = 'change_this_to_a_strong_secret_key';

// Settings
$CHUNK_SIZE = 8 * 1024 * 1024; // 8 MB
$DELETE_PARTS_AFTER_JOIN = false;
$UNZIP_AFTER_JOIN = true;
$UNZIP_DESTINATION = __DIR__;

if (!isset($_GET['key']) || $_GET['key'] !== $ACCESS_KEY) {
    http_response_code(403);
    echo '<h2>Access Denied</h2>';
    exit;
}

set_time_limit(0);
error_reporting(E_ALL);

// List files in current directory (excluding this script)
$files = array_values(array_filter(scandir(__DIR__), function ($f) {
    return is_file(__DIR__ . DIRECTORY_SEPARATOR . $f) && $f !== basename(__FILE__);
}));

function safe_filename($name) {
    return (bool) preg_match('/^[\w\-\.\(\) ]+$/u', $name);
}

function extract_last_number($s) {
    if (preg_match('/(\d+)(?!.*\d)/', $s, $m)) {
        return (int) $m[1];
    }
    return null;
}

// Sort files by last number if available
usort($files, function ($a, $b) {
    $na = extract_last_number($a);
    $nb = extract_last_number($b);
    if ($na === null && $nb === null) return strcmp($a, $b);
    if ($na === null) return 1;
    if ($nb === null) return -1;
    return $na - $nb;
});

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $base = trim($_POST['auto_base'] ?? '');
    $output = trim($_POST['output_name'] ?? '');
    $selected = isset($_POST['selected']) ? (array) $_POST['selected'] : [];

    if ($base !== '') {
        foreach ($files as $f) {
            if (stripos($f, $base) !== false && !in_array($f, $selected, true)) {
                $selected[] = $f;
            }
        }
        usort($selected, function ($a, $b) {
            return (extract_last_number($a) ?? 0) <=> (extract_last_number($b) ?? 0);
        });
    }

    if (empty($selected)) {
        $message = 'No files selected.';
    } elseif ($output === '') {
        $message = 'Please enter an output file name.';
    } else {
        foreach ($selected as $s) {
            if (!safe_filename($s) || !file_exists(__DIR__ . DIRECTORY_SEPARATOR . $s)) {
                $message = 'Invalid or missing file: ' . htmlspecialchars($s);
                break;
            }
        }
        if ($message === '') {
            $outPath = __DIR__ . DIRECTORY_SEPARATOR . $output;
            if (file_exists($outPath)) {
                $message = 'Output file already exists: ' . htmlspecialchars($output);
            } else {
                $out = @fopen($outPath, 'wb');
                if (!$out) {
                    $message = 'Error: Cannot create output file (check permissions).';
                } else {
                    $failed = false;
                    foreach ($selected as $p) {
                        $inPath = __DIR__ . DIRECTORY_SEPARATOR . $p;
                        $in = @fopen($inPath, 'rb');
                        if (!$in) { $failed = true; $message = 'Cannot open part: ' . htmlspecialchars($p); break; }
                        while (!feof($in)) {
                            $data = fread($in, $CHUNK_SIZE);
                            if ($data === false) { $failed = true; break; }
                            if (fwrite($out, $data) === false) { $failed = true; break; }
                        }
                        fclose($in);
                        if ($failed) break;
                    }
                    fclose($out);

                    if ($failed) {
                        @unlink($outPath);
                        if ($message === '') $message = 'Error writing output file.';
                    } else {
                        if ($DELETE_PARTS_AFTER_JOIN) {
                            foreach ($selected as $p) @unlink(__DIR__ . DIRECTORY_SEPARATOR . $p);
                        }
                        $message = 'Successfully merged: ' . htmlspecialchars($output) . ' — ' . number_format(filesize($outPath)) . ' bytes.';

                        if ($UNZIP_AFTER_JOIN && preg_match('/\.zip$/i', $output)) {
                            if (!extension_loaded('zip')) {
                                $message .= ' However, ZipArchive extension is not enabled.';
                            } else {
                                $zip = new ZipArchive();
                                if ($zip->open($outPath) === true) {
                                    $res = $zip->extractTo($UNZIP_DESTINATION);
                                    $zip->close();
                                    if ($res) {
                                        $message .= ' ZIP extracted successfully to: ' . htmlspecialchars($UNZIP_DESTINATION);
                                    } else {
                                        $message .= ' Error extracting ZIP.';
                                    }
                                } else {
                                    $message .= ' Failed to open ZIP file for extraction.';
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Join and Unzip ZIP Parts</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f6f8fa;padding:18px}
.container{max-width:920px;margin:auto;background:#fff;padding:18px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.06)}
.file{display:inline-block;border:1px solid #e1e1e1;padding:8px;border-radius:6px;margin:6px}
.notice{background:#eef;padding:10px;border-radius:6px;margin-bottom:12px}
</style>
</head>
<body>
<div class="container">
<h2>Join and Unzip ZIP Parts</h2>

<div class="notice">
1) Place this script in the same folder as your ZIP parts.<br>
2) Change <code>$ACCESS_KEY</code> at the top for security.<br>
3) Make sure <strong>ZipArchive</strong> PHP extension is enabled if you want to extract automatically.
</div>

<?php if ($message !== ''): ?>
    <div style="background:#e8ffe8;padding:10px;border-radius:6px;margin-bottom:12px;"><?= nl2br(htmlspecialchars($message)) ?></div>
<?php endif; ?>

<form method="post">
<label>Base name (optional):</label><br>
<input type="text" name="auto_base" style="width:60%;padding:6px;margin-top:6px" placeholder="e.g., myfile.zip or myfile"><br><br>

<label>Available files (check to select):</label>
<div style="margin-top:8px">
    <?php foreach ($files as $f): ?>
        <label class="file">
            <input type="checkbox" name="selected[]" value="<?= htmlspecialchars($f) ?>"> <?= htmlspecialchars($f) ?>
            <div style="font-size:12px;color:#666;"><?= number_format(filesize(__DIR__.DIRECTORY_SEPARATOR.$f)) ?> bytes</div>
        </label>
    <?php endforeach; ?>
</div>

<br>
<label>Output file name (e.g., myfull.zip):</label><br>
<input type="text" name="output_name" style="width:50%;padding:6px;margin-top:6px" required><br><br>

<button type="submit" style="padding:10px 14px;border-radius:6px">Join and Unzip</button>
</form>
</div>
</body>
</html>
