<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');


$conn->set_charset("utf8mb4");

$id = $_GET['id'] ?? 0;

/* ดึงกิจกรรม */
$event_sql="SELECT * FROM events WHERE event_id=?";
$stmt=$conn->prepare($event_sql);
$stmt->bind_param("i",$id);
$stmt->execute();
$event=$stmt->get_result()->fetch_assoc();

$status = strtolower(trim($event['status']));

if($status != 'active' && $status != 'inactive'){
    die("กิจกรรมปิดแล้ว แก้ไขไม่ได้");
}

/* ดึง item ทั้งหมด */
$items_query="SELECT item_id,sub_category,item_name,unit
FROM donation_items
WHERE is_active=1
ORDER BY item_id ASC";

$items_result=$conn->query($items_query);

/* ดึง target เดิม */
$target_sql="SELECT item_id,target_quantity
FROM event_item_targets
WHERE event_id=?";

$stmt=$conn->prepare($target_sql);
$stmt->bind_param("i",$id);
$stmt->execute();
$target_result=$stmt->get_result();

$targets=[];
while($row=$target_result->fetch_assoc()){
$targets[$row['item_id']]=$row['target_quantity'];
}


/* ===== บันทึก ===== */

if($_SERVER['REQUEST_METHOD']=="POST"){

$event_name=$_POST['event_name'];
$event_date=$_POST['event_date'];
$venue=$_POST['venue'];
$highlights=$_POST['highlights'];
$schedule_range=$_POST['schedule_range'];

$event_image=$event['event_image'];

if(!empty($_FILES['event_image']['name'])){

$target_dir="img/";
$file_ext=pathinfo($_FILES['event_image']['name'],PATHINFO_EXTENSION);
$new_name=uniqid().".".$file_ext;

move_uploaded_file($_FILES['event_image']['tmp_name'],$target_dir.$new_name);

$event_image=$new_name;

}

$selected_items=$_POST['items']??[];
$targets_post=$_POST['targets']??[];

$conn->begin_transaction();

try{

/* update event */

$sql="UPDATE events
SET event_name=?,event_date=?,Location=?,highlights=?,schedule_range=?,event_image=?
WHERE event_id=?";

$stmt=$conn->prepare($sql);
$stmt->bind_param("ssssssi",
$event_name,
$event_date,
$venue,
$highlights,
$schedule_range,
$event_image,
$id);

$stmt->execute();

/* ลบ target เดิม */

$conn->query("DELETE FROM event_item_targets WHERE event_id=$id");

/* เพิ่มใหม่ */

if(!empty($selected_items)){

$sql_target="INSERT INTO event_item_targets
(event_id,item_id,target_quantity,current_received)
VALUES (?,?,?,0)";

$stmt_target=$conn->prepare($sql_target);

foreach($selected_items as $item_id){

$qty=(float)($targets_post[$item_id]??0);

if($qty>0){

$stmt_target->bind_param("iid",$id,$item_id,$qty);
$stmt_target->execute();

}

}

}

$conn->commit();

header("Location: manage_events.php");
exit();

}catch(Exception $e){

$conn->rollback();
echo "เกิดข้อผิดพลาด";

}

}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขกิจกรรม - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --text-sub: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
        }

        body {
            font-family: 'IBM Plex Sans Thai', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            display: flex;
        }

        /* Sidebar Space */
        .main-content {
            flex: 1;
            margin-left: 260px; /* ปรับให้เท่ากับหน้าเดิม */
            padding: 30px;
            min-width: 0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Header */
        .header-flex {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .btn-back {
            text-decoration: none;
            color: var(--text-sub);
            font-size: 1.2rem;
            transition: 0.3s;
        }

        .btn-back:hover { color: var(--primary); }

        h2 { font-weight: 700; margin: 0; font-size: 1.6rem; }

        /* Form Layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        @media (max-width: 850px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--white);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        .card-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
        }

        /* Form Controls */
        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        input[type="text"], 
        input[type="date"], 
        input[type="number"], 
        textarea, 
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        textarea { resize: vertical; min-height: 80px; }

        /* Image Preview */
        .img-preview-box {
            margin-bottom: 10px;
            position: relative;
        }

        .img-preview-box img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        /* Table Style */
        .table-scroll {
            max-height: 450px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        table { width: 100%; border-collapse: collapse; }

        th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            color: var(--text-sub);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

        .qty-input {
            width: 80px !important;
            padding: 6px !important;
            text-align: center;
            margin-bottom: 0 !important;
        }

        /* Custom Checkbox */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        /* Footer / Button */
        .form-footer {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-submit {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>

    <?php include('menu_admin.php'); ?>

    <main class="main-content">
        <div class="container">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="header-flex">
                    <a href="manage_events.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
                    <h2>แก้ไขรายละเอียดกิจกรรม</h2>
                </div>

                <div class="form-grid">
                    
                    <div class="card">
                        <div class="card-title"><i class="fa-solid fa-pen-to-square"></i> ข้อมูลพื้นฐาน</div>
                        
                        <div class="form-group">
                            <label>ชื่อกิจกรรม</label>
                            <input type="text" name="event_name" value="<?= htmlspecialchars($event['event_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>รูปภาพหน้าปก</label>
                            <?php if($event['event_image']): ?>
                                <div class="img-preview-box">
                                    <img src="img/<?= $event['event_image'] ?>" id="preview">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="event_image" accept="image/*" onchange="previewImg(this)">
                        </div>

                        <div class="form-group">
                            <label>กำหนดการ / ช่วงเวลา</label>
                            <input type="text" name="schedule_range" placeholder="เช่น 12 - 15 เมษายน" value="<?= htmlspecialchars($event['schedule_range']) ?>">
                        </div>

                        <div class="form-group">
                            <label>สถานที่</label>
                            <input type="text" name="venue" value="<?= htmlspecialchars($event['Location']) ?>">
                        </div>

                        <div class="form-group">
                            <label>วันที่จัดกิจกรรม (สำหรับระบบปฏิทิน)</label>
                            <input type="date" name="event_date" value="<?= $event['event_date'] ?>">
                        </div>

                        <div class="form-group">
                            <label>รายละเอียดกิจกรรมย่อย (Highlights)</label>
                            <textarea name="highlights"><?= htmlspecialchars($event['highlights']) ?></textarea>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title"><i class="fa-solid fa-boxes-stacked"></i> ตั้งค่าเป้าหมายรับบริจาค</div>
                        
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th width="40">เลือก</th>
                                        <th>รายการสิ่งของ</th>
                                        <th class="text-center">เป้าหมาย (กก.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item=$items_result->fetch_assoc()): ?>
                                        <?php
                                            $item_id=$item['item_id'];
                                            $checked=isset($targets[$item_id]);
                                            $qty=$targets[$item_id]??0;
                                        ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="items[]" value="<?= $item_id ?>" <?= $checked?'checked':'' ?>>
                                            </td>
                                            <td>
                                                <div style="font-weight: 500;"><?= htmlspecialchars($item['item_name']) ?></div>
                                                <div style="font-size: 11px; color: var(--text-sub);"><?= htmlspecialchars($item['sub_category']) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <input class="qty-input" type="number" step="0.01" name="targets[<?= $item_id ?>]" value="<?= $qty ?>">
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="font-size: 12px; color: var(--text-sub); margin-top: 15px;">
                            * เลือกรายการและระบุจำนวนที่ต้องการรับบริจาค (ระบุ 0 หากไม่ต้องการตั้งเป้าหมาย)
                        </p>
                    </div>

                </div>

                <div class="form-footer">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> บันทึกการแก้ไข
                    </button>
                </div>

            </form>
        </div>
    </main>

    <script>
        // ฟังก์ชัน Preview รูปภาพแบบ Real-time
        function previewImg(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('preview');
                    if(preview) {
                        preview.src = e.target.result;
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>