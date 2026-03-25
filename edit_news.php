<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

$conn = new mysqli("localhost", "root", "", "project");
$conn->set_charset("utf8mb4");

if (!isset($_GET['id'])) { die("ไม่พบรหัสข่าว"); }
$news_id = intval($_GET['id']);

// 1. ดึงข้อมูลข่าว
$stmt_get = $conn->prepare("SELECT * FROM news_update WHERE news_id = ?");
$stmt_get->bind_param("i", $news_id);
$stmt_get->execute();
$news = $stmt_get->get_result()->fetch_assoc();
if (!$news) { die("ไม่พบข้อมูลข่าว"); }

// 2. Logic การลบรูปเดิม (PHP ส่วนนี้จะถูกเรียกเมื่อกดยืนยันลบ)
if (isset($_GET['del_img'])) {
    $img_id = intval($_GET['del_img']);
    $stmt_img = $conn->prepare("SELECT image_path FROM news_images WHERE image_id = ?");
    $stmt_img->bind_param("i", $img_id);
    $stmt_img->execute();
    $img_info = $stmt_img->get_result()->fetch_assoc();

    if ($img_info) {
        $full_path = "img/" . $img_info['image_path'];
        if (file_exists($full_path)) { unlink($full_path); }
        $stmt_del = $conn->prepare("DELETE FROM news_images WHERE image_id = ?");
        $stmt_del->bind_param("i", $img_id);
        $stmt_del->execute();
    }
    header("Location: edit_news.php?id=$news_id&status=deleted"); 
    exit();
}

// 3. Logic บันทึกแก้ไข
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $detail = $_POST['detail'];
    $event_id = empty($_POST['event_id']) ? NULL : intval($_POST['event_id']);

    $stmt_upd = $conn->prepare("UPDATE news_update SET title=?, detail=?, event_id=? WHERE news_id=?");
    $stmt_upd->bind_param("ssii", $title, $detail, $event_id, $news_id);
    
    if ($stmt_upd->execute()) {
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $new_name = "news_" . uniqid() . "_" . $key . "." . $ext;
                    if (move_uploaded_file($tmp_name, "img/" . $new_name)) {
                        $stmt_ins_img = $conn->prepare("INSERT INTO news_images (news_id, image_path) VALUES (?, ?)");
                        $stmt_ins_img->bind_param("is", $news_id, $new_name);
                        $stmt_ins_img->execute();
                    }
                }
            }
        }
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข่าว - <?= htmlspecialchars($news['title']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Anuphan', sans-serif; background: #f4f7f6; }
        .admin-card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: none; }
        
        /* สไตล์รูปปัจจุบัน */
        .current-img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .img-item { position: relative; aspect-ratio: 4/3; }
        .img-item img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-del-old { position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border: none; border-radius: 50%; width: 26px; height: 26px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: 0.3s; }
        .btn-del-old:hover { transform: scale(1.2); background: #eb2f06; }

        /* สไตล์เพิ่มรูปใหม่ */
        .preview-box { border: 2px dashed #d1d8e0; border-radius: 12px; padding: 15px; text-align: center; background: #fff; transition: 0.3s; }
        .preview-box:hover { border-color: #3742fa; }
        .preview-img { width: 100%; height: 100px; object-fit: cover; border-radius: 8px; display: none; margin-bottom: 10px; }
        
        .btn-main { background: #2f3542; color: white; padding: 12px 40px; border-radius: 50px; font-weight: 500; border: none; transition: 0.3s; }
        .btn-main:hover { background: #000; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="admin-card">
                <h3 class="fw-bold mb-4"><i class="fas fa-edit text-primary me-2"></i> แก้ไขข้อมูลข่าว</h3>
                
                <form id="newsForm" method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">กิจกรรม</label>
                            <select name="event_id" class="form-select">
                                <option value="">กิจกรรมทั่วไป</option>
                                <?php
                                $evs = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC");
                                while($ev = $evs->fetch_assoc()):
                                ?>
                                <option value="<?= $ev['event_id'] ?>" <?= ($ev['event_id'] == $news['event_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ev['event_name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">หัวข้อข่าว</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($news['title']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">เนื้อหาข่าว</label>
                        <textarea name="detail" class="form-control" rows="5"><?= htmlspecialchars($news['detail']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">รูปภาพปัจจุบัน (กดเพื่อลบ)</label>
                        <div class="current-img-grid">
                            <?php
                            $imgs = $conn->query("SELECT * FROM news_images WHERE news_id = $news_id");
                            if ($imgs->num_rows > 0):
                                while($im = $imgs->fetch_assoc()):
                            ?>
                                <div class="img-item">
                                    <img src="img/<?= htmlspecialchars($im['image_path']) ?>" onerror="this.src='https://via.placeholder.com/150'">
                                    <button type="button" class="btn-del-old" onclick="confirmDelete(<?= $im['image_id'] ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endwhile; else: echo "<p class='text-muted small'>ไม่มีรูปภาพ</p>"; endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">เพิ่มรูปภาพประกอบใหม่</label>
                        <div id="image-container" class="row g-3">
                            <div class="col-md-4">
                                <div class="preview-box">
                                    <img id="img-0" class="preview-img">
                                    <input type="file" name="images[]" class="form-control form-control-sm file-input" accept="image/*" data-index="0">
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-image-btn" class="btn btn-sm btn-outline-primary mt-3">
                            <i class="fas fa-plus me-1"></i> เพิ่มช่องรูปภาพ
                        </button>
                    </div>

                    <div class="text-center mt-5">
                        <button type="submit" class="btn btn-main px-5">บันทึกการแก้ไข</button>
                        <a href="manage_news.php" class="btn btn-light rounded-pill ms-2 px-4 border">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    // 1. จัดการการเพิ่มช่องรูปใหม่และ Preview
    let imageCount = 1;
    const container = document.querySelector('#image-container');
    const addButton = document.querySelector('#add-image-btn');

    const createImageSlot = (index) => {
        const col = document.createElement('div');
        col.className = 'col-md-4';
        col.innerHTML = `
            <div class="preview-box position-relative">
                <img id="img-${index}" class="preview-img">
                <input type="file" name="images[]" class="form-control form-control-sm file-input" accept="image/*" data-index="${index}">
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle remove-btn" style="width:22px;height:22px;padding:0;">&times;</button>
            </div>`;
        return col;
    };

    container.addEventListener('change', (e) => {
        if (!e.target.classList.contains('file-input')) return;
        const [file] = e.target.files;
        if (file) {
            const preview = document.querySelector(`#img-${e.target.dataset.index}`);
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        }
    });

    addButton.addEventListener('click', () => container.appendChild(createImageSlot(imageCount++)));
    container.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-btn')) e.target.closest('.col-md-4').remove();
    });

    // 2. SweetAlert2 ตอนบันทึกสำเร็จ
    <?php if ($success): ?>
    Swal.fire({
        icon: 'success', title: 'สำเร็จ!', text: 'แก้ไขข้อมูลเรียบร้อยแล้ว',
        confirmButtonColor: '#2f3542', timer: 2000
    }).then(() => { window.location = 'manage_news.php'; });
    <?php endif; ?>

    // 3. ยืนยันก่อนลบรูปเก่า
    window.confirmDelete = (imgId) => {
        Swal.fire({
            title: 'คุณต้องการลบรูปนี้?',
            text: "เมื่อลบแล้วจะไม่สามารถกู้คืนได้",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4757',
            cancelButtonColor: '#2f3542',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = `edit_news.php?id=<?= $news_id ?>&del_img=${imgId}`;
            }
        });
    };

    // เช็คกรณีเพิ่งลบรูปเสร็จ
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'deleted') {
        Swal.fire({ icon: 'success', title: 'ลบรูปภาพแล้ว', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    }
})();
</script>

</body>
</html>