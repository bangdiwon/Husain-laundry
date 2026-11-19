<?php
session_start();
$_SESSION = [];
$c = session_get_cookie_params();
setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
session_destroy();
header('Location: login.php');
