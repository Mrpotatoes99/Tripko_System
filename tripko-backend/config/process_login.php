<?php
session_start();
// After successful login
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_type_id'] = $user['user_type_id'];
$_SESSION['username'] = $user['username'];