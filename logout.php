<?php
require __DIR__ . '/config/app.php';
$_SESSION = [];
session_destroy();
redirect('login.php');
