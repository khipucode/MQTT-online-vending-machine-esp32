<?php
session_start();
session_destroy();
header('Location: login.php'); // Redireciona para o login do admin
exit;