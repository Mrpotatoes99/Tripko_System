<?php
session_start();
session_destroy();
header("Location: ../tripko-frontend/file_html/SignUp_LogIn_Form.php");
exit();
?>