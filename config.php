<?php
// config.php
session_start();

// put right after session_start();
$_dir = str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME'] ?? ""));
$_dir = rtrim($_dir, "/");
if ($_dir === "/" || $_dir === "\\") { $_dir = ""; }
if ($_dir !== "" && $_dir[0] !== "/") { $_dir = "/".$_dir; }
define("BASE_URL", $_dir);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "labr";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function require_login(){
    if (empty($_SESSION['user_id'])) {
        header("Location: ".BASE_URL."/login.php");
        exit;
    }
}
