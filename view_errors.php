<?php
echo "<h1>Recent PHP Errors</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#000;color:#0f0;} pre{white-space:pre-wrap;}</style>";

$error_log = 'C:/xampp/apache/logs/error.log';

if (file_exists($error_log)) {
    $lines = file($error_log);
    $recent = array_slice($lines, -50); // Last 50 lines
    
    echo "<h2>Last 50 lines from error.log:</h2>";
    echo "<pre>";
    foreach ($recent as $line) {
        if (stripos($line, 'repair_shop') !== false || stripos($line, 'booking') !== false) {
            echo "<span style='color:#ff0;'>" . htmlspecialchars($line) . "</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log not found at: $error_log</p>";
}
?>
