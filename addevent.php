<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');
// --- 1. Logic การบันทึกข้อมูล ---
ini_set('display_errors', 1); error_reporting(E_ALL);
$servername = "localhost"; $username = "root"; $password = ""; $dbname = "project";
$status = "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// ดึงข้อมูลรายการของบริจาค
$items_query = "SELECT item_id, sub_category, item_name, unit FROM donation_items WHERE is_active = 1 ORDER BY item_id ASC";
$items_result = $conn->query($items_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = trim($_POST['event_name'] ?? '');
    $event_date = trim($_POST['event_date'] ?? ''); 
    $event_time = trim($_POST['event_time'] ?? '');
    $venue      = trim($_POST['venue'] ?? ''); 
    $highlights = trim($_POST['highlights'] ?? ''); 
    $schedule_range = trim($_POST['schedule_range'] ?? '');

    // ระบบจัดการรูปภาพ
    $event_image = "";
    if (!empty($_FILES['event_image']['name'])) {
        $target_dir = "img/"; 
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_extension = pathinfo($_FILES["event_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        if (move_uploaded_file($_FILES["event_image"]["tmp_name"], $target_file)) {
            $event_image = $new_filename;
        }
    }

    $selected_items = $_POST['items'] ?? []; 
    $target_quantities = $_POST['targets'] ?? [];

    // --- CHECK คำเตือนฝั่ง PHP: ต้องมีชื่อกิจกรรม วันที่ และต้องเลือกของอย่างน้อย 1 รายการ ---
    if (!empty($event_name) && !empty($event_date) && !empty($selected_items)) {
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO events (event_name, event_date, event_time, Location, highlights, schedule_range, event_image) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $event_name, $event_date, $event_time, $venue, $highlights, $schedule_range, $event_image);
            $stmt->execute();
            $new_event_id = $conn->insert_id;
            $stmt->close();

            $sql_target = "INSERT INTO event_item_targets (event_id, item_id, target_quantity, current_received) VALUES (?, ?, ?, 0)";
            $stmt_target = $conn->prepare($sql_target);
            foreach ($selected_items as $item_id) {
                $qty = (float)($target_quantities[$item_id] ?? 0);
                if ($qty > 0) {
                    $stmt_target->bind_param("iid", $new_event_id, $item_id, $qty);
                    $stmt_target->execute();
                }
            }
            $stmt_target->close();
            
            $conn->commit();
            $status = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $status = "error";
        }
    } else {
        // ถ้าไม่ติ๊กเลือกของเลย จะตกมาที่สถานะ missing นี้จ้ะ
        $status = "missing";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างกิจกรรมใหม่ | Admin Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@300;800&family=IBM+Plex+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --emerald: #10b981; --dark-green: #064e3b; --slate: #64748b; --light-gray: #f8fafc; --white: #ffffff; }
        body { font-family: 'IBM Plex Sans Thai', sans-serif; background: #fbfcfb; color: var(--dark-green); margin: 0; padding: 0; }
        .container { max-width: 1100px; margin-right: 30px; margin-left: auto; padding: 40px 20px; }
        .header { margin-bottom: 40px; }
        .header p { color: var(--emerald); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; margin-bottom: 8px; letter-spacing: 2px; font-family: 'Bricolage Grotesque', sans-serif; }
        .header h1 { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800; margin: 0; letter-spacing: -1.5px; font-family: 'Bricolage Grotesque', sans-serif; }
        .header h1 span { color: var(--emerald); }
        .admin-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 40px; }
        .form-card { background: var(--white); border-radius: 32px; padding: 40px; border: 1px solid #edf2f0; box-shadow: 0 15px 35px rgba(6, 78, 59, 0.03); }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--dark-green); }
        .input-group input, .input-group textarea { width: 100%; padding: 14px 18px; border-radius: 14px; border: 1.5px solid #eee; background: var(--light-gray); font-family: inherit; font-size: 0.95rem; transition: all 0.2s; box-sizing: border-box; }
        .input-group input:focus, .input-group textarea:focus { outline: none; border-color: var(--emerald); background: var(--white); box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.05); }
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .items-section { margin-top: 30px; border-top: 2px dashed #eee; padding-top: 20px; }
        .items-table-wrapper { max-height: 400px; overflow-y: auto; border: 1px solid #f1f5f9; border-radius: 16px; margin-top: 10px; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .items-table th { background: #f8fafc; padding: 12px; text-align: left; position: sticky; top: 0; z-index: 1; }
        .items-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
        .qty-input { width: 80px !important; padding: 8px !important; text-align: center; }
        .qty-input:disabled { background: #e2e8f0; cursor: not-allowed; border-color: #eee; }
        .btn-save { width: 100%; padding: 18px; border-radius: 16px; border: none; background: var(--dark-green); color: var(--white); font-weight: 700; cursor: pointer; transition: all 0.3s ease; font-size: 1rem; margin-top: 20px; }
        .btn-save:hover { background: var(--emerald); transform: translateY(-2px); }
        .preview-box { background: #f0f4f2; padding: 25px; border-radius: 24px; margin-top: 20px; }
        .preview-box h4 { margin: 0 0 10px 0; font-size: 1rem; }
        .preview-tag { display: inline-block; background: var(--white); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; margin: 3px; border: 1px solid #e0eadd; }
        .next-icon { width: 50px; height: 50px; background-color: #f0f2f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--dark-green); transition: 0.3s; margin: 40px auto 0; }
        .next-icon:hover { background: var(--dark-green); color: white; transform: translateX(-5px); }
        @media (max-width: 850px) { .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include 'menu_admin.php'; ?>
<div class="container">
    <header class="header">
        <p>Admin Hub</p>
        <h1>เพิ่ม<span>กิจกรรม & คลังบริจาค.</span></h1>
    </header>

    <div class="admin-grid">
        <div class="admin-intro">
            <h2>รายละเอียดกิจกรรม</h2>
            <p>เลือกหมวดหมู่หลักและกำหนดเป้าหมายการบริจาค (หน่วย: กก.)</p>
            
            <div class="preview-box">
                <h4><i class="fa-solid fa-star" style="color: #fbbf24;"></i> ตัวอย่างกิจกรรมย่อย</h4>
                <div class="preview-tag">บอกรักแม่</div>
                <div class="preview-tag">แจกเสื้อผ้า</div>
                <div class="preview-tag">เลี้ยงอาหาร</div>
            </div>
        </div>

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data" id="eventForm">
                <div class="input-group">
                    <label>ชื่อกิจกรรมหลัก</label>
                    <input type="text" name="event_name" placeholder="เช่น อาสาครูดอยครั้งที่ 5" required>
                </div>

                <div class="input-group">
                    <label><i class="fa-regular fa-image"></i> รูปภาพปกกิจกรรม</label>
                    <input type="file" name="event_image" accept="image/*">
                </div>

                <div class="input-group">
                    <label>กำหนดการ (ช่วงวันที่)</label>
                    <input type="text" name="schedule_range" placeholder="เช่น 12 – 14 สิงหาคม 2569" required>
                </div>

                <div class="input-group">
                    <label>กิจกรรมย่อย</label>
                    <textarea name="highlights" rows="2" placeholder="บอกรักแม่, เกมมหาสนุก"></textarea>
                </div>

                <div class="grid-inputs">
                    <div class="input-group">
                        <label>วันที่เริ่ม</label>
                        <input type="date" name="event_date" required>
                    </div>
                    <div class="input-group">
                        <label>สถานที่</label>
                        <input type="text" name="venue" placeholder="ระบุสถานที่" required>
                    </div>
                </div>

                <div class="items-section">
                    <label style="font-weight: 700; color: var(--emerald);"><i class="fa-solid fa-boxes-stacked"></i> เลือกเป้าหมายรับบริจาค (กิโลกรัม)</label>
                    <div class="items-table-wrapper">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th width="50">เลือก</th>
                                    <th>หมวดหมู่หลัก</th>
                                    <th width="120">เป้าหมาย (กก.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($items_result && $items_result->num_rows > 0): ?>
                                    <?php while($item = $items_result->fetch_assoc()): ?>
                                    <tr>
                                        <td align="center">
                                            <input type="checkbox" name="items[]" value="<?= $item['item_id'] ?>" class="item-checkbox">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($item['sub_category']) ?></strong>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="number" name="targets[<?= $item['item_id'] ?>]" class="qty-input target-input" placeholder="0" step="0.01" min="0.01" disabled>
                                                <span style="font-size: 0.75rem; color: #94a3b8;">กก.</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <button type="submit" class="btn-save">บันทึกกิจกรรมและเปิดคลังบริจาค</button>
            </form>
        </div>
    </div>

    <a href="manage_events.php" style="text-decoration: none;">
        <div class="next-icon"><i class="fa-solid fa-arrow-left"></i></div>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // --- CHECK คำเตือน 1: แสดงผลลัพธ์จาก PHP ---
document.addEventListener('DOMContentLoaded', function() {
    <?php if($status === "success"): ?>
        Swal.fire({ 
            icon: 'success', 
            title: 'บันทึกเรียบร้อย', 
            text: 'กิจกรรมและคลังบริจาคถูกสร้างแล้ว', 
            confirmButtonColor: '#10b981' 
        }).then((result) => {
            // เมื่อผู้ใช้กดปุ่มตกลง หรือปิด Alert
            if (result.isConfirmed || result.isDismissed) {
                window.location.href = 'manage_events.php'; // เด้งไปหน้าจัดการ
            }
        });
    <?php elseif($status === "error"): ?>
        Swal.fire({ 
            icon: 'error', 
            title: 'ผิดพลาด', 
            text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่' 
        });
    <?php elseif($status === "missing"): ?>
        Swal.fire({ 
            icon: 'warning', 
            title: 'ข้อมูลไม่สมบูรณ์', 
            text: 'กรุณากรอกข้อมูลและติ๊กเลือกคลังบริจาคอย่างน้อย 1 รายการ', 
            confirmButtonColor: '#10b981' 
        });
    <?php endif; ?>
});
    // --- CHECK คำเตือน 1: แสดงผลลัพธ์จาก PHP ---
    document.addEventListener('DOMContentLoaded', function() {
        <?php if($status === "success"): ?>
            Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', text: 'กิจกรรมและคลังบริจาคถูกสร้างแล้ว', confirmButtonColor: '#10b981' });
        <?php elseif($status === "error"): ?>
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่' });
        <?php elseif($status === "missing"): ?>
            Swal.fire({ icon: 'warning', title: 'ข้อมูลไม่สมบูรณ์', text: 'กรุณากรอกข้อมูลและติ๊กเลือกคลังบริจาคอย่างน้อย 1 รายการ', confirmButtonColor: '#10b981' });
        <?php endif; ?>
    });

    // --- CHECK คำเตือน 2: ระบบเปิดช่องกรอกเลขเมื่อติ๊กเลือก ---
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const row = this.closest('tr');
            const input = row.querySelector('.target-input');
            if (this.checked) {
                input.disabled = false;
                input.required = true; // บังคับว่าถ้าติ๊กแล้วต้องกรอกเลข
                input.focus();
            } else {
                input.disabled = true;
                input.required = false;
                input.value = ''; // ล้างค่าออกถ้าเลิกติ๊ก
            }
        });
    });

    // --- CHECK คำเตือน 3: ตรวจสอบก่อนส่งฟอร์ม (ถ้าไม่ติ๊กเลย ห้ามไปต่อ) ---
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
        
        if (checkedCount === 0) {
            e.preventDefault(); // สั่งหยุดส่งฟอร์มทันที
            Swal.fire({
                icon: 'warning',
                title: 'ยังไม่ได้เลือกคลัง!',
                text: 'กรุณาติ๊กเลือกหมวดหมู่ที่ต้องการรับบริจาคอย่างน้อย 1 รายการ',
                confirmButtonColor: '#10b981'
            });
            return false;
        }
    });
</script>
</body>
</html>