<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');



// รับค่าจาก Request (รองรับทั้ง GET และ POST)
$donation_id = intval($_REQUEST['id'] ?? 0);
$new_status = $_REQUEST['status'] ?? '';

// ตรวจสอบความถูกต้องของข้อมูลเบื้องต้น
if (!in_array($new_status, ['Approved', 'Rejected']) || $donation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit();
}

$conn->begin_transaction();

try {
    // 1. ดึงข้อมูลรายการบริจาคมาตรวจสอบและ Lock Row ไว้
    $stmt = $conn->prepare("
        SELECT d.*, i.item_name 
        FROM donations d 
        JOIN donation_items i ON d.item_id = i.item_id 
        WHERE d.donation_id = ? 
        FOR UPDATE
    ");
    $stmt->bind_param("i", $donation_id);
    $stmt->execute();
    $donation = $stmt->get_result()->fetch_assoc();

    if (!$donation) {
        throw new Exception("ไม่พบรายการบริจาคในระบบ");
    }

    if ($donation['status'] !== 'Pending') {
        throw new Exception("รายการนี้ถูกดำเนินการไปแล้ว (Status: " . $donation['status'] . ")");
    }

    // 2. อัปเดตสถานะในตาราง donations (ยอดนี้จะถูก SUM ไปโชว์ที่ Dashboard)
    $update_stmt = $conn->prepare("UPDATE donations SET status = ? WHERE donation_id = ?");
    $update_stmt->bind_param("si", $new_status, $donation_id);
    $update_stmt->execute();

    // 3. Logic การจัดการคลัง (Inventory) กรณีอนุมัติ
    if ($new_status === 'Approved' && !empty($donation['event_id'])) {
        $event_id = $donation['event_id'];
        $item_id = $donation['item_id'];
        $qty = $donation['quantity'];

        // อัปเดตยอดเข้าตารางเป้าหมาย (event_item_targets) เพื่อไม่ให้ช่อง current_received ว่าง
        $upd_target_stmt = $conn->prepare("UPDATE event_item_targets SET current_received = current_received + ? WHERE event_id = ? AND item_id = ?");
        $upd_target_stmt->bind_param("dii", $qty, $event_id, $item_id);
        $upd_target_stmt->execute();

        // นำจำนวนของเพิ่มเข้าสู่คลังหลัก (inventory_stock) 
        // ให้ inventory_stock เป็นศูนย์กลางเก็บยอดของจริง (Physical Inventory) ทั้งหมด
        $inv_stmt = $conn->prepare("
            INSERT INTO inventory_stock (item_id, quantity) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $inv_stmt->bind_param("idd", $item_id, $qty, $qty);
        $inv_stmt->execute();
    }

    $conn->commit();

    // ส่งผลลัพธ์กลับ
    echo json_encode([
        'success' => true,
        'message' => ($new_status === 'Approved' ? 'อนุมัติเรียบร้อยแล้ว' : 'ปฏิเสธรายการแล้ว'),
        'new_status' => $new_status
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>