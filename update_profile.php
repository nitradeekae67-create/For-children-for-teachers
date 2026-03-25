<?php
session_start();
include('connect.php');

// ตั้งค่าให้ส่งกลับเป็น JSON เพื่อใช้งานกับ SweetAlert2 (Fetch API)
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'เซสชันหมดอายุ กรุณาล็อกอินใหม่']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $phone      = $_POST['phone'];
    $province   = $_POST['province'];
    $address    = $_POST['address'];
    
    // ข้อมูลรหัสผ่าน
    $current_pw = $_POST['current_password'];
    $new_pw     = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    // 1. ตรวจสอบว่าอีเมลซ้ำกับคนอื่นหรือไม่
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    if ($check_email->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'อีเมลนี้ถูกใช้งานแล้วโดยผู้ใช้อื่น']);
        exit();
    }

    // 2. กรณีต้องการเปลี่ยนรหัสผ่าน
    $password_update_sql = "";
    if (!empty($new_pw)) {
        // ดึงรหัสผ่านเดิมมาเช็ค
        $stmt_pw = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_pw->bind_param("i", $user_id);
        $stmt_pw->execute();
        $user_pw_hash = $stmt_pw->get_result()->fetch_assoc()['password'];

        // ตรวจสอบรหัสผ่านปัจจุบัน (ใช้ password_verify)
        if (!password_verify($current_pw, $user_pw_hash)) {
            echo json_encode(['success' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
            exit();
        }

        // เข้ารหัสรหัสผ่านใหม่
        $new_hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
        $password_update_sql = ", password = '$new_hashed_pw' ";
    }

    // 3. อัปเดตข้อมูลลงฐานข้อมูล
    // หมายเหตุ: ใช้ String Injection เฉพาะส่วนรหัสผ่านที่ผ่านการตรวจสอบแล้ว ส่วนอื่นใช้ Prepare Statement เพื่อความปลอดภัย
    $sql = "UPDATE users SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?, 
            province = ?, 
            address = ? 
            $password_update_sql 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $province, $address, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลสำเร็จแล้ว']);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}