<?php
require 'auth.php';
checkRole(['admin']);

// 1. นำเข้าไฟล์เชื่อมต่อ (ในนี้ควรมี $conn = new mysqli... อยู่แล้ว)
include('connect.php');

// ❌ ลบบรรทัด $conn = new mysqli($servername, $username...) ทิ้งไปเลยครับ
// ❌ ลบบรรทัด if ($conn->connect_error) { ... } ทิ้งไปด้วย

// 2. ตรวจสอบว่ามีตัวแปร $conn มาจาก connect.php จริงไหม
if (!isset($conn)) {
    die("Error: ไม่พบการเชื่อมต่อฐานข้อมูลจากไฟล์ connect.php");
}

$conn->set_charset("utf8mb4");

// ---------------- ดำเนินการลบต่อได้เลย ----------------
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id); 
    
    if ($stmt->execute()) {
        header("Location: showuser.php?status=success"); 
        exit();
    } else {
        header("Location: showuser.php?status=error");
        exit();
    }
} else {
    header("Location: showuser.php?status=invalid");
    exit();
}
?>