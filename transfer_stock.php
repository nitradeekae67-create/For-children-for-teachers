<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

$conn->set_charset("utf8mb4");

// รับค่าและทำความสะอาดข้อมูล
$item_id  = isset($_POST['item_id'])  ? intval($_POST['item_id'])   : 0;
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id'])  : 0;
$amount   = isset($_POST['amount'])   ? floatval($_POST['amount'])  : 0;

if ($item_id <= 0 || $event_id <= 0 || $amount <= 0) {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ถูกต้อง"]);
    exit();
}

$conn->begin_transaction();

try {

    // -------------------------------------------------------
    // 1. คำนวณยอดคลังรวมจริง (เหมือน logic หน้าบริจาค/dashboard)
    //
    //    บริจาคตรง (event_id = 999/0/NULL)  → นับเต็ม
    //    กิจกรรม Active                      → นับเต็ม ยังไม่หัก
    //    กิจกรรม Inactive (ของไม่ครบ)        → นับเต็ม ไม่หัก
    //    กิจกรรม Closed + ของครบเป้า         → นับเฉพาะส่วนเกิน (actual - target)
    // -------------------------------------------------------

    // ส่วนที่ 1: บริจาคตรง + Active + Inactive → นับเต็ม
    $stmt_direct = $conn->prepare("
        SELECT COALESCE(SUM(d.quantity), 0) as total
        FROM donations d
        LEFT JOIN events e ON d.event_id = e.event_id
        WHERE d.item_id = ?
          AND d.status = 'Approved'
          AND (
              d.event_id = 999
              OR d.event_id = 0
              OR d.event_id IS NULL
              OR e.status = 'Active'
              OR e.status = 'Inactive'
          )
    ");
    $stmt_direct->bind_param("i", $item_id);
    $stmt_direct->execute();
    $total_direct = floatval($stmt_direct->get_result()->fetch_assoc()['total'] ?? 0);

    // ส่วนที่ 2: กิจกรรม Closed + ของครบเป้า → นับเฉพาะส่วนเกิน
    $stmt_excess = $conn->prepare("
        SELECT COALESCE(SUM(GREATEST(actual.total_rec - t.target_quantity, 0)), 0) as total
        FROM event_item_targets t
        JOIN events e ON t.event_id = e.event_id
        JOIN (
            SELECT event_id, item_id, SUM(quantity) as total_rec
            FROM donations
            WHERE status = 'Approved'
            GROUP BY event_id, item_id
        ) actual ON actual.event_id = t.event_id AND actual.item_id = t.item_id
        WHERE t.item_id = ?
          AND e.status = 'Closed'
          AND actual.total_rec >= t.target_quantity
    ");
    $stmt_excess->bind_param("i", $item_id);
    $stmt_excess->execute();
    $total_excess = floatval($stmt_excess->get_result()->fetch_assoc()['total'] ?? 0);

    $total_stock = $total_direct + $total_excess;

    // -------------------------------------------------------
    // 2. คำนวณของที่ "ถูกจอง" โดยกิจกรรม Active ที่ยังขาดอยู่
    //    จอง = MAX(target - received, 0) เฉพาะกิจกรรม Active
    //    Inactive ไม่จอง เพราะของไม่ครบและไม่หักอยู่แล้ว
    // -------------------------------------------------------
    $stmt_reserved = $conn->prepare("
        SELECT COALESCE(SUM(
            GREATEST(
                t.target_quantity - COALESCE((
                    SELECT SUM(d.quantity)
                    FROM donations d
                    WHERE d.event_id = t.event_id
                      AND d.item_id = t.item_id
                      AND d.status = 'Approved'
                ), 0),
            0)
        ), 0) as reserved
        FROM event_item_targets t
        JOIN events e ON t.event_id = e.event_id
        WHERE t.item_id = ?
          AND e.status = 'Active'
    ");
    $stmt_reserved->bind_param("i", $item_id);
    $stmt_reserved->execute();
    $reserved = floatval($stmt_reserved->get_result()->fetch_assoc()['reserved'] ?? 0);

    // -------------------------------------------------------
    // 3. ของว่างที่ดึงได้จริง
    // -------------------------------------------------------
    $available = $total_stock - $reserved;

    if ($available < $amount) {
        throw new Exception(
            "ของในคลังไม่พอ! (ปัจจุบันมียอดว่างให้ดึงได้เพียง " . number_format($available, 2) . " กิโลกรัม)"
        );
    }

    // -------------------------------------------------------
    // 4. เพิ่ม donation เข้ากิจกรรมนั้น (Approved ทันที)
    //    ไม่ต้องลด inventory_stock เพราะของยังอยู่ในคลัง
    //    แค่บันทึกว่ากิจกรรมนี้ได้รับของเพิ่ม
    // -------------------------------------------------------
    $stmt_insert = $conn->prepare("
        INSERT INTO donations (event_id, item_id, quantity, status)
        VALUES (?, ?, ?, 'Approved')
    ");
    $stmt_insert->bind_param("iid", $event_id, $item_id, $amount);
    $stmt_insert->execute();

    $conn->commit();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
exit();
?>