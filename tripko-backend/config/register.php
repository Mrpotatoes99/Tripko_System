<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../tripko-frontend/SignUp_LogIn_Form.php');
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: ../tripko-frontend/SignUp_LogIn_Form.php?error=empty');
    exit();
}

try {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO user (username, password) VALUES (:username, :password)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed);

    $stmt->execute();
    header('Location: ../tripko-frontend/SignUp_LogIn_Form.php?registered=1');
    exit();
} catch (PDOException $e) {
    
    error_log('Registration error: ' . $e->getMessage());
    header('Location: ../tripko-frontend/SignUp_LogIn_Form.php?error=system');
    exit();
}
