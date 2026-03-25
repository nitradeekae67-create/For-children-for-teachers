<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');
header('Content-Type: application/json');

// รับค่า ID ของรายการที่บริจาคมา
$donation_id = isset($_POST['donation_id']) ? intval($_POST['donation_id']) : 0;
$status = "Approved"; // สถานะที่จะปรับคือยืนยันแล้ว

if ($donation_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID ไม่ถูกต้อง"]);
    exit();
}

$conn->begin_transaction();

try {
    // 1. ดึงข้อมูลรายการบริจาคนี้มาดูว่าคือไอเทมอะไร และจำนวนเท่าไหร่
    $stmt_ref = $conn->prepare("SELECT item_id, quantity, event_id FROM donations WHERE donation_id = ?");
    $stmt_ref->bind_param("i", $donation_id);
    $stmt_ref->execute();
    $res_ref = $stmt_ref->get_result();
    
    if ($res_ref->num_rows === 0) {
        throw new Exception("ไม่พบรายการบริจาคนี้");
    }
    
    $data = $res_ref->fetch_assoc();
    $item_id = $data['item_id'];
    $qty = $data['quantity'];

    // 2. อัปเดตสถานะในตาราง donations เป็น 'Approved'
    // ทันทีที่สถานะเป็น Approved กราฟในหน้า Dashboard จะดึงยอดนี้ไปโชว์ทันที
    $stmt_up = $conn->prepare("UPDATE donations SET status = ? WHERE donation_id = ?");
    $stmt_up->bind_param("si", $status, $donation_id);
    $stmt_up->execute();

    // 2.5 เพิ่มยอดเข้าตารางเป้าหมาย (event_item_targets) เพื่อไม่ให้ช่อง current_received ว่าง
    if (!empty($data['event_id'])) {
        $event_id = $data['event_id'];
        $stmt_target = $conn->prepare("UPDATE event_item_targets SET current_received = current_received + ? WHERE event_id = ? AND item_id = ?");
        $stmt_target->bind_param("dii", $qty, $event_id, $item_id);
        $stmt_target->execute();
    }

    // 3. นำจำนวนของเพิ่มเข้าสู่คลังหลัก (inventory_stock) 
    // ใช้ ON DUPLICATE KEY UPDATE เพื่อให้ถ้ามีไอเทมอยู่แล้วก็แค่บวกเพิ่ม ถ้าไม่มีให้สร้างใหม่
    $stmt_stock = $conn->prepare("INSERT INTO inventory_stock (item_id, quantity) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $stmt_stock->bind_param("idd", $item_id, $qty, $qty);
    $stmt_stock->execute();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "ยืนยันรายการและนำของเข้าคลังเรียบร้อย"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->close();
exit();
?>