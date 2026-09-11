<?php
// config.php
session_start();

$dir = str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME'] ?? ""));
$dir = rtrim($dir, "/");
if ($dir === "/" || $dir === "\\") { $dir = ""; }
if ($dir !== "" && $dir[0] !== "/") { $dir = "/" . $dir; }
define("BASE_URL", $dir);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "labr";

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    $conn = null;
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function require_login(){
    if (empty($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}
