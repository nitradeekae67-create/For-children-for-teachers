<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

$conn->set_charset("utf8mb4");

$id = intval($_GET['id']);

// 🔥 ฟังก์ชันรูป (ใช้เหมือนหน้า manage)
function getImage($img){
    if(empty($img)) return "https://via.placeholder.com/100";

    if(file_exists($img)) return $img;
    if(file_exists("uploads/".$img)) return "uploads/".$img;

    return "https://via.placeholder.com/100";
}

// โหลดข้อมูลเดิม
$data = $conn->query("
SELECT d.*, di.item_name 
FROM donations d
LEFT JOIN donation_items di ON d.item_id = di.item_id
WHERE donation_id=$id
")->fetch_assoc();

if(!$data){
    exit("ไม่พบข้อมูล");
}

// ================= SAVE =================
if(isset($_POST['save'])){

    $qty = intval($_POST['quantity']);
    $status = $_POST['status'];

    $newImagePath = $data['image_path_1']; // ค่าเดิม

    // 🔥 ถ้ามีการอัปโหลดรูปใหม่
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

        $fileName = $_FILES['image']['name'];
        $tmpName  = $_FILES['image']['tmp_name'];

        // ตั้งชื่อใหม่กันชน
        $newName = time() . "_" . $fileName;
        $uploadPath = "uploads/" . $newName;

        // ย้ายไฟล์
        if(move_uploaded_file($tmpName, $uploadPath)){

            // 🔥 ลบรูปเก่า (ถ้ามี)
            if(!empty($data['image_path_1']) && file_exists($data['image_path_1'])){
                unlink($data['image_path_1']);
            }
            if(!empty($data['image_path_1']) && file_exists("uploads/".$data['image_path_1'])){
                unlink("uploads/".$data['image_path_1']);
            }

            $newImagePath = $uploadPath;
        }
    }

    // update
    $stmt = $conn->prepare("
        UPDATE donations 
        SET quantity=?, status=?, image_path_1=? 
        WHERE donation_id=?
    ");
    $stmt->bind_param("issi", $qty, $status, $newImagePath, $id);
    $stmt->execute();

    header("Location: manage_donations.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไข</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">

<h3>✏️ แก้ไขรายการ (แก้รูปได้)</h3>

<form method="post" enctype="multipart/form-data">

    <div class="mb-3">
        <label>ชื่อของ</label>
        <input type="text" class="form-control" value="<?= $data['item_name'] ?>" disabled>
    </div>

    <div class="mb-3">
        <label>จำนวน</label>
        <input type="number" name="quantity" class="form-control" value="<?= $data['quantity'] ?>" required>
    </div>

    <div class="mb-3">
        <label>สถานะ</label>
        <select name="status" class="form-control">
            <option value="Pending" <?= $data['status']=='Pending'?'selected':'' ?>>Pending</option>
            <option value="Approved" <?= $data['status']=='Approved'?'selected':'' ?>>Approved</option>
            <option value="Rejected" <?= $data['status']=='Rejected'?'selected':'' ?>>Rejected</option>
        </select>
    </div>

    <!-- 🔥 รูปเดิม -->
    <div class="mb-3">
        <label>รูปปัจจุบัน</label><br>
        <img src="<?= getImage($data['image_path_1']) ?>" width="120" style="border-radius:10px;">
    </div>

    <!-- 🔥 อัปโหลดใหม่ -->
    <div class="mb-3">
        <label>เปลี่ยนรูป (ถ้าไม่เปลี่ยน ไม่ต้องเลือก)</label>
        <input type="file" name="image" class="form-control">
    </div>

    <button name="save" class="btn btn-success">บันทึก</button>
    <a href="manage_donations.php" class="btn btn-secondary">กลับ</a>

</form>

</body>
</html>