<?php
require_once 'config/app.php';
require_once 'includes/Session.php';
require_once 'includes/Auth.php';

Session::init();
$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
?>