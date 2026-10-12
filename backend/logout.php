<?php
require __DIR__ . '/common.php';
require_post();
$_SESSION = [];
session_regenerate_id(true);
respond(['success' => true, 'message' => 'You have logged out.']);
