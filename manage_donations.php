<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');


$conn->set_charset("utf8mb4");

// 🔥 ฟังก์ชันแก้ปัญหารูปไม่ขึ้น (โคตรสำคัญ)
function getImage($img){
    if(empty($img)) return "https://via.placeholder.com/60";

    // กรณีเก็บ path เต็ม
    if(file_exists($img)) return $img;

    // กรณีเก็บแค่ชื่อไฟล์
    if(file_exists("uploads/".$img)) return "uploads/".$img;

    return "https://via.placeholder.com/60";
}

// ลบ
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM donations WHERE donation_id = $id");
    header("Location: manage_donations.php");
    exit();
}

// ดึงข้อมูล
$res = $conn->query("
SELECT d.*, u.first_name, u.last_name, di.item_name
FROM donations d
JOIN users u ON d.user_id = u.id
LEFT JOIN donation_items di ON d.item_id = di.item_id
ORDER BY d.donation_id DESC
");
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการบริจาค</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">

<h3 class="mb-4">📦 จัดการรายการบริจาค</h3>

<table class="table table-bordered align-middle">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>ผู้บริจาค</th>
    <th>ของ</th>
    <th>จำนวน</th>
    <th>รูป</th>
    <th>สถานะ</th>
    <th>จัดการ</th>
</tr>
</thead>

<tbody>
<?php while($row = $res->fetch_assoc()): ?>
<tr>
    <td><?= $row['donation_id'] ?></td>

    <td>
        <?= $row['first_name'].' '.$row['last_name'] ?>
    </td>

    <td><?= $row['item_name'] ?></td>

    <td><?= $row['quantity'] ?></td>

    <td>
        <img src="<?= getImage($row['image_path_1']) ?>" 
             width="60" height="60" style="object-fit:cover; border-radius:8px;">
    </td>

    <td><?= $row['status'] ?></td>

    <td>
        <a href="edit_donation.php?id=<?= $row['donation_id'] ?>" class="btn btn-warning btn-sm">แก้ไข</a>
        <a href="?delete=<?= $row['donation_id'] ?>" 
           onclick="return confirm('ลบจริง?')" 
           class="btn btn-danger btn-sm">ลบ</a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>