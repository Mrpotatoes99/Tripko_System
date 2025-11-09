<?php
// Shim file: provide same behavior as canonical `Database.php` by requiring it.
// This avoids duplicate class definitions and ensures case-insensitive includes work on Linux.
require_once __DIR__ . '/Database.php';
?>
