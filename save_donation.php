<?php
session_start();
include('connect.php');
header('Content-Type: application/json');
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'คุณต้องเข้าสู่ระบบก่อน']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$event_id = (int)($_POST['event_id'] ?? 0);
$items = $_POST['items'] ?? [];
$image_path = "";

// อัปโหลดรูป
if (isset($_FILES['donation_image_1']) && $_FILES['donation_image_1']['error'] == 0) {
    $dir = "uploads/donations/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $filename = time() . "_" . $_FILES['donation_image_1']['name'];
    move_uploaded_file($_FILES['donation_image_1']['tmp_name'], $dir . $filename);
    $image_path = $dir . $filename;
}

$conn->begin_transaction();
try {
    $stmt_donation = $conn->prepare("
        INSERT INTO donations (user_id, event_id, item_id, quantity, image_path_1, status) 
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");

    foreach ($items as $item_id => $qty) {
        $item_id = (int)$item_id;
        $qty = (float)$qty;
        if ($qty <= 0) continue;

        $stmt_donation->bind_param("iiiis", $user_id, $event_id, $item_id, $qty, $image_path);
        $stmt_donation->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'บันทึกการบริจาคเรียบร้อย รอการอนุมัติ']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>