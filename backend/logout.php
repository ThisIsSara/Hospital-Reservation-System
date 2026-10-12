<?php
require __DIR__ . '/common.php';
require_post();
$_SESSION = [];
session_regenerate_id(true);
flash('You have logged out.', 'success');
redirect_to('login.php');
