<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (function_exists('exec')) { echo "exec exists\n"; } else { echo "exec missing\n"; }
if (function_exists('shell_exec')) { echo "shell_exec exists\n"; } else { echo "shell_exec missing\n"; }
if (function_exists('passthru')) { echo "passthru exists\n"; } else { echo "passthru missing\n"; }
if (function_exists('system')) { echo "system exists\n"; } else { echo "system missing\n"; }
echo "</pre>";
