<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'james@1234567');
define('DB_NAME', 'student_result_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("
    <div style='font-family:sans-serif; padding:40px; text-align:center; color:#c0392b;'>
        <h2>Database Connection Failed</h2>
        <p>" . $conn->connect_error . "</p>
        <p style='color:#666;'>Make sure XAMPP is running and database is imported.</p>
    </div>
    ");
}

$conn->set_charset("utf8");

session_start();

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function sanitize($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}

function getGrade($marks) {
    if ($marks >= 75) return 'A';
    elseif ($marks >= 60) return 'B';
    elseif ($marks >= 50) return 'C';
    elseif ($marks >= 40) return 'D';
    else return 'F';
}

function getGradeClass($grade) {
    switch($grade) {
        case 'A': return 'grade-a';
        case 'B': return 'grade-b';
        case 'C': return 'grade-c';
        case 'D': return 'grade-d';
        default: return 'grade-f';
    }
}
?>