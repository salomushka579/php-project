<?php
function loadEnv($file) {
    if (!file_exists($file)) {
        throw new Exception(".env file not found.");
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // skip comments
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Store the variable in $_ENV superglobal
        $_ENV[$name] = $value;
    }
}
