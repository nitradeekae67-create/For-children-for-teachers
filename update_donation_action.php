<?php
session_start();
include('connect.php');

if (!isset($_SESSION['user_id'])) { exit("Access Denied"); }
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = (int)$_POST['id'];

    if ($action == 'edit') {
        $new_qty = (int)$_POST['qty'];
        $update_fields = "quantity = ?";
        $params = [$new_qty];
        $types = "i";

        // --- เช็คว่ามีการอัปโหลดรูปใหม่มาไหม ---
        if (isset($_FILES['new_proof']) && $_FILES['new_proof']['error'] == 0) {
            $target_dir = "uploads/donations/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            
            $ext = strtolower(pathinfo($_FILES["new_proof"]["name"], PATHINFO_EXTENSION));
            $new_filename = "update_" . $id . "_" . time() . "." . $ext;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["new_proof"]["tmp_name"], $target_file)) {
                $update_fields .= ", image_path_1 = ?";
                $params[] = $target_file;
                $types .= "s";
            }
        }

        $params[] = $id;
        $params[] = $user_id;
        $types .= "ii";

        $stmt = $conn->prepare("UPDATE donations SET $update_fields WHERE donation_id = ? AND user_id = ? AND status = 'Pending'");
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            header("Location: history.php?success=edit");
        } else {
            header("Location: history.php?error=1");
        }
    }
} 
// ส่วนของ Cancel (GET)
elseif (isset($_GET['action']) && $_GET['action'] == 'cancel') {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM donations WHERE donation_id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: history.php?success=cancel");
}