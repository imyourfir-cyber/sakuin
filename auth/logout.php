<?php
require '../config/koneksi.php';
$_SESSION = [];
session_destroy();
go('auth/login.php');
