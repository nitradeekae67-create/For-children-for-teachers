<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');
header('Content-Type: application/json');

if (!isset($_POST['event_id'], $_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'missing data']);
    exit();
}

$event_id = (int)$_POST['event_id'];
$new_status = trim($_POST['status']);

$conn->begin_transaction();

try {

    // 1. ดึงข้อมูลสถานะเดิมและ Lock row ป้องกัน Race condition
    $stmt_old = $conn->prepare("SELECT status FROM events WHERE event_id = ? FOR UPDATE");
    $stmt_old->bind_param("i", $event_id);
    $stmt_old->execute();
    $old_res = $stmt_old->get_result();
    
    if($old_res->num_rows === 0) {
        throw new Exception("ไม่พบกิจกรรมนี้ในระบบ");
    }
    
    $old_status = $old_res->fetch_assoc()['status'];
    
    // ถ้ากิจกรรมโดนปิดไปแล้ว จะไม่ทำงานซ้ำ (ป้องกันการหักคลังซ้ำซ้อน)
    if ($old_status === 'Closed' && $new_status === 'Closed') {
        throw new Exception("กิจกรรมนี้ถูกปิดและหักสต็อกไปแล้ว");
    }

    // อัปเดตสถานะ (และเพิ่มรอบถ้าเป็น Closed)
    if ($new_status === 'Closed') {
        $stmt = $conn->prepare("UPDATE events SET status = ?, closed_count = closed_count + 1 WHERE event_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE events SET status = ? WHERE event_id = ?");
    }
    
    $stmt->bind_param("si", $new_status, $event_id);
    $stmt->execute();

    // ❌ ถ้าไม่ใช่ Closed = ห้ามหัก
    if ($new_status !== 'Closed') {
        $conn->commit();
        echo json_encode(['success' => true]);
        exit();
    }

    // 🔥 Closed เท่านั้นถึงหัก
    $stmt_items = $conn->prepare("
        SELECT item_id, target_quantity 
        FROM event_item_targets 
        WHERE event_id = ?
    ");
    $stmt_items->bind_param("i", $event_id);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();

    while ($row = $res_items->fetch_assoc()) {

        $item_id = (int)$row['item_id'];
        $target_qty = (float)$row['target_quantity'];

        // รวมของที่อนุมัติแล้ว
        $stmt_sum = $conn->prepare("
            SELECT SUM(quantity) as total 
            FROM donations 
            WHERE event_id = ? AND item_id = ? AND status = 'Approved'
        ");
        $stmt_sum->bind_param("ii", $event_id, $item_id);
        $stmt_sum->execute();
        $sum = $stmt_sum->get_result()->fetch_assoc()['total'] ?? 0;

        $use_qty = min($sum, $target_qty);

        if ($use_qty > 0) {
            $stmt_update = $conn->prepare("
                UPDATE inventory_stock 
                SET quantity = GREATEST(0, quantity - ?)
                WHERE item_id = ?
            ");
            $stmt_update->bind_param("di", $use_qty, $item_id);
            $stmt_update->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}