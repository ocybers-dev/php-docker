<?php
require_once 'db.php';

// 销毁session
session_destroy();

// 重定向到主页
header('Location: index.php');
exit;
?>
