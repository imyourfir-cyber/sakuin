<?php
require 'config/koneksi.php';
go(empty($_SESSION['uid']) ? 'auth/login.php' : 'pages/dashboard.php');
