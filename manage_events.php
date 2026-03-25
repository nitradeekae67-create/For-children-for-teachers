```php
<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$sql = "SELECT * FROM events ORDER BY event_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการกิจกรรม - Admin</title>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root{
--primary:#10b981;
--primary-dark:#059669;
--bg-body:#f8fafc;
--text-main:#1e293b;
--text-sub:#64748b;
}

body{
font-family:'IBM Plex Sans Thai',sans-serif;
background-color:var(--bg-body);
margin:0;
display:flex;
}

.main-content{
flex:1;
margin-left:260px;
padding:30px;
min-width:0;
}

.container{
max-width:1200px;
margin:0 auto;
}

.header-flex{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:25px;
}

h2 {
    font-size: 2rem; /* ขนาดใหญ่ขึ้น */
    font-weight: 700;
    color: var(--primary-dark); /* ใช้สีเขียวเข้ม */
    margin: 0;
    padding-left: 15px;
    border-left: 6px solid var(--primary); /* แถบสีเขียวหนาด้านข้าง */
    line-height: 1.2;
    display: flex;
    align-items: center;
}

.btn-add-event{
background-color:var(--primary);
color:white;
text-decoration:none;
padding:10px 20px;
border-radius:12px;
font-weight:500;
font-size:14px;
display:flex;
align-items:center;
gap:8px;
transition:0.3s;
box-shadow:0 4px 10px rgba(16,185,129,0.2);
}

.btn-add-event:hover{
background-color:var(--primary-dark);
transform:translateY(-2px);
}

.table-wrapper{
background:white;
border-radius:16px;
box-shadow:0 4px 20px rgba(0,0,0,0.05);
overflow:hidden;
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#f8fafc;
padding:16px;
text-align:left;
font-size:0.85rem;
color:var(--text-sub);
font-weight:600;
border-bottom:1px solid #edf2f7;
}

td{
padding:16px;
border-bottom:1px solid #f1f5f9;
vertical-align:middle;
}

tr:last-child td{border:none;}
tr:hover{background-color:#fafafa;}

.event-info{
display:flex;
align-items:center;
gap:15px;
}

.event-img{
width:60px;
height:45px;
object-fit:cover;
border-radius:8px;
}

.no-img{
width:60px;
height:45px;
background:#f1f5f9;
border-radius:8px;
display:flex;
align-items:center;
justify-content:center;
font-size:10px;
color:#cbd5e1;
}

.event-name{
font-weight:600;
color:var(--text-main);
display:block;
}

.event-loc{
font-size:0.8rem;
color:var(--text-sub);
}

.badge{
padding:5px 12px;
border-radius:20px;
font-size:12px;
font-weight:600;
display:inline-flex;
align-items:center;
gap:5px;
}

.badge-open{background:#dcfce7;color:#166534;}
.badge-closed{background:#fee2e2;color:#991b1b;}

.btn-tool{
width:34px;
height:34px;
display:inline-flex;
align-items:center;
justify-content:center;
border-radius:8px;
text-decoration:none;
font-size:14px;
transition:0.2s;
margin:0 2px;
}

.btn-edit{background:#eff6ff;color:#2563eb;}
.btn-edit:hover{background:#2563eb;color:white;}

.btn-delete{background:#fff1f2;color:#e11d48;}
.btn-delete:hover{background:#e11d48;color:white;}

.text-disabled{
color:#cbd5e1;
font-size:13px;
font-style:italic;
}

.text-center{text-align:center;}

@media(max-width:992px){
.main-content{margin-left:0;padding:15px;}
}
</style>
</head>

<body>

<?php include('menu_admin.php'); ?>

<main class="main-content">
<div class="container">

<div class="header-flex">
<h2>จัดการกิจกรรม</h2>
<a href="addevent.php" class="btn-add-event">
<i class="fa fa-plus-circle"></i> เพิ่มกิจกรรมใหม่
</a>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>ข้อมูลกิจกรรม</th>
<th>ช่วงเวลา</th>
<th class="text-center">สถานะ</th>
<th class="text-center">เครื่องมือ</th>
</tr>
</thead>

<tbody>

<?php if($result && $result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>

<tr>

<td>

<div class="event-info">

<?php 
$img="img/".$row['event_image'];
if(!empty($row['event_image']) && file_exists($img)): ?>

<img src="<?= $img ?>" class="event-img">

<?php else: ?>

<div class="no-img">No Image</div>

<?php endif; ?>

<div>
<span class="event-name"><?= htmlspecialchars($row['event_name']) ?></span>
<span class="event-loc">
<i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($row['Location']) ?>
</span>
</div>

</div>

</td>

<td style="font-size:14px;color:#475569;">
<i class="fa-regular fa-calendar-check" style="color:var(--primary);"></i>
<?= htmlspecialchars($row['schedule_range']) ?>
</td>

<td class="text-center">
    <?php if($row['status'] == 'Active'): ?>
        <span class="badge badge-open">
            <i class="fa fa-circle" style="font-size:6px;"></i> เปิดอยู่
        </span>

    <?php elseif($row['status'] == 'Inactive'): ?>
        <span class="badge" style="background:#fef3c7; color:#945309; border: 1px solid #fde68a;">
            <i class="fa fa-clock-rotate-left"></i> ปิดชั่วคราว
        </span>

    <?php else: ?>
        <span class="badge badge-closed">
            <i class="fa fa-circle" style="font-size:6px;"></i> ปิดกิจกรรม
        </span>
    <?php endif; ?>
</td>

<td class="text-center">
    <?php 
    // เงื่อนไข: ถ้าเป็น Active หรือ Inactive ให้แก้ได้ | ถ้าเป็น Closed ให้แก้ไม่ได้
    if($row['status'] == 'Active' || $row['status'] == 'Inactive'): 
    ?>
        <a title="แก้ไข" class="btn-tool btn-edit"
           href="edit_event.php?id=<?= $row['event_id'] ?>">
            <i class="fa fa-edit"></i>
        </a>

        <a title="ลบ" class="btn-tool btn-delete"
           onclick="return confirm('ต้องการลบกิจกรรมนี้หรือไม่ ?')"
           href="delete_event.php?id=<?= $row['event_id'] ?>">
            <i class="fa fa-trash"></i>
        </a>

    <?php else: ?>
        <span class="text-disabled">ปิดการแก้ไข</span>
    <?php endif; ?>
</td>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="5" class="text-center" style="padding:50px;color:#94a3b8;">
ไม่พบข้อมูลกิจกรรม
</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

</div>
</main>

</body>
</html>

