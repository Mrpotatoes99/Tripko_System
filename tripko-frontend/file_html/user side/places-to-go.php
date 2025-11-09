<?php
// Lightweight wrapper for the Beach (Places to Go) category using the shared template.
// Previous inline implementation replaced for consistency and to enable municipality filter.
session_start();

$CATEGORY_KEY = 'Beach';
$HERO_TITLE   = 'Where the Waves Meet Your Soul';
$TAGS = ['Beach','Swimming','Scenic'];
require __DIR__ . '/category_page_template.php';
?>