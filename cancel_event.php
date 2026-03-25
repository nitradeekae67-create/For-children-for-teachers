<?php
session_start();
header('Content-Type: application/json');
include('connect.php'); // ตรวจสอบชื่อไฟล์เชื่อมต่อให้ถูกต้อง
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['user_id']) || !isset($_POST['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];

// 1. เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

// 2. ตรวจสอบสถานะและวันที่ของ Event นั้นๆ
$stmt = $conn->prepare("SELECT e.event_date, a.status 
                        FROM events e 
                        INNER JOIN join_event a ON e.event_id = a.event_id 
                        WHERE a.user_id = ? AND a.event_id = ?");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการเข้าร่วมกิจกรรม']);
    exit();
}

if ($data['status'] == 0) {
    echo json_encode(['success' => false, 'message' => 'กิจกรรมนี้ถูกยกเลิกไปแล้ว']);
    exit();
}

// 3. ตรวจสอบเงื่อนไข 3 วัน (Server-side validation)
$eventDate = new DateTime($data['event_date']);
$eventDate->setTime(0,0,0);
$today = new DateTime('today');

$diff = $today->diff($eventDate);
$daysLeft = (int)$diff->format("%r%a");

if ($daysLeft < 3) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถยกเลิกได้ เนื่องจากต้องยกเลิกก่อนเริ่มงานอย่างน้อย 3 วัน']);
    exit();
}

// 4. ทำการอัปเดตสถานะเป็น 0 (ยกเลิก)
$update_stmt = $conn->prepare("UPDATE join_event SET status = 0 WHERE user_id = ? AND event_id = ?");
$update_stmt->bind_param("ii", $user_id, $event_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'ยกเลิกการเข้าร่วมสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล']);
}

$stmt->close();
$update_stmt->close();
$conn->close();
?>