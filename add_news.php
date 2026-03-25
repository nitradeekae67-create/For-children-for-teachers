<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$status = "";

$sql_get_events = "SELECT event_id, event_name, event_date, status 
                   FROM events 
                   ORDER BY FIELD(status, 'Active', 'Inactive', 'Closed'), event_date DESC";
$events_result = $conn->query($sql_get_events);

$ongoing_events = [];
$closed_events  = [];
$years_set      = [];

if ($events_result && $events_result->num_rows > 0) {
    while ($row = $events_result->fetch_assoc()) {
        $year = date('Y', strtotime($row['event_date']));
        $years_set[$year] = true;
        if ($row['status'] === 'Closed') {
            $closed_events[] = $row;
        } else {
            $ongoing_events[] = $row;
        }
    }
}

$years = array_keys($years_set);
rsort($years);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $title    = $_POST['title'];
    $detail   = $_POST['detail'];
    $user_id  = $_SESSION['user_id'];

    $sql_news = "INSERT INTO news_update (user_id, event_id, title, detail, status) VALUES (?, ?, ?, ?, 1)";
    $stmt_news = $conn->prepare($sql_news);
    $stmt_news->bind_param("iiss", $user_id, $event_id, $title, $detail);

    if ($stmt_news->execute()) {
        $inserted_id = $conn->insert_id;
        $upload_dir  = "img/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        if (!empty($_FILES['images']['name'][0])) {
            $sql_img  = "INSERT INTO news_images (news_id, image_path) VALUES (?, ?)";
            $stmt_img = $conn->prepare($sql_img);
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $ext      = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $new_name = "news_" . uniqid() . "_" . $key . "." . $ext;
                    $target   = $upload_dir . $new_name;
                    if (move_uploaded_file($tmp_name, $target)) {
                        $stmt_img->bind_param("is", $inserted_id, $new_name);
                        $stmt_img->execute();
                    }
                }
            }
        }
        $status = "success";
    } else {
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข่าวประชาสัมพันธ์ - ระบบปันสุข</title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary-color: #1e293b; }
        body { font-family: 'Anuphan', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .container { max-width: 1200px; margin-right: 30px; margin-left: auto; padding: 40px 20px; }
        .admin-card {
            background: white; border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0; padding: 40px; margin-top: 40px;
        }
        .form-label { font-weight: 600; color: #475569; }
        .btn-main {
            background: var(--primary-color); color: white; border-radius: 12px;
            padding: 15px; border: none; transition: 0.3s; font-size: 1.1rem;
        }
        .btn-main:hover { background: #000; transform: translateY(-2px); color: white; }
        .preview-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 15px; margin-top: 15px;
        }
        .preview-item {
            border: 2px dashed #cbd5e1; border-radius: 16px;
            padding: 15px; text-align: center; background: #f1f5f9;
        }
        .preview-item img { width: 100%; height: 110px; object-fit: cover; border-radius: 10px; display: none; }

        /* Year select */
        .year-select-wrap { margin-bottom: 10px; }
        .year-select-wrap select { width: 100%; padding: 8px 14px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; font-family: 'Anuphan', sans-serif; font-size: 0.9rem; font-weight: 600; color: #1e293b; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2364748b'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
        .year-select-wrap select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 0.2rem rgba(99,102,241,0.15); }

        /* Select2 */
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single { height: calc(3.5rem + 2px); border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.8rem 0.75rem; font-family: 'Anuphan', sans-serif; font-size: 1rem; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(3.5rem + 2px); }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; color: #1e293b; padding-left: 0; }
        .select2-dropdown { border: 1px solid #dee2e6; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); font-family: 'Anuphan', sans-serif; }
        .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-family: 'Anuphan', sans-serif; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #6366f1; }
        .select2-container--default .select2-results__group { color: #0f172a; font-weight: 700; background: #f1f5f9; padding: 8px 12px; font-size: 0.85rem; }
        .select2-results__option { padding: 8px 16px; font-size: 0.95rem; }
        .select2-container--default.select2-container--focus .select2-selection--single { border-color: #6366f1; box-shadow: 0 0 0 0.2rem rgba(99,102,241,0.15); }

        /* Status badge */
        .status-badge-box { display: inline-flex; align-items: center; gap: 6px; font-size: 0.83rem; font-weight: 700; padding: 4px 14px; border-radius: 50px; margin-top: 8px; }
        .status-badge-box.open   { background: #e6f4ea; color: #1e7e34; }
        .status-badge-box.closed { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<?php include 'menu_admin.php'; ?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="admin-card">
                <div class="text-center mb-5">
                    <h2 class="fw-bold"><i class="fas fa-bullhorn me-3 text-primary"></i>เผยแพร่ข่าวประชาสัมพันธ์</h2>
                    <p class="text-muted">สร้างข่าวสารและผูกกับกิจกรรมอาสาแยกตามสถานะกิจกรรม</p>
                </div>

                <form id="newsForm" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-primary">
                                <i class="fas fa-calendar-check me-2"></i>เลือกกิจกรรมที่เกี่ยวข้อง
                            </label>

                            <div class="year-select-wrap">
                                <select id="yearFilter" onchange="filterByYear(this.value)">
                                    <option value="all">ทุกปี</option>
                                    <?php foreach ($years as $y): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <select name="event_id" id="eventSelect" required>
                                <option value="">-- พิมพ์เพื่อค้นหากิจกรรม --</option>

                                                    <?php 
                        $active_events = array_filter($ongoing_events, function($ev) { return strtolower($ev['status']) === 'active'; });
                        if (!empty($active_events)): ?>
                        <optgroup label="🟢 กิจกรรมที่กำลังดำเนินอยู่">
                            <?php foreach ($active_events as $ev): ?>
                            <option value="<?= $ev['event_id'] ?>"
                                    data-year="<?= date('Y', strtotime($ev['event_date'])) ?>"
                                    data-ev-status="active"
                                    data-group="ongoing">
                                <?= htmlspecialchars($ev['event_name']) ?> (<?= date('Y', strtotime($ev['event_date'])) ?>) — เปิดอยู่
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>

                        <?php 
                        $inactive_events = array_filter($ongoing_events, function($ev) { return strtolower($ev['status']) === 'inactive'; });
                        if (!empty($inactive_events)): ?>
                        <optgroup label="🟠 กิจกรรมที่ปิดชั่วคราว">
                            <?php foreach ($inactive_events as $ev): ?>
                            <option value="<?= $ev['event_id'] ?>"
                                    data-year="<?= date('Y', strtotime($ev['event_date'])) ?>"
                                    data-ev-status="inactive"
                                    data-group="ongoing">
                                <?= htmlspecialchars($ev['event_name']) ?> (<?= date('Y', strtotime($ev['event_date'])) ?>) — ปิดชั่วคราว
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>

                        <?php if (!empty($closed_events)): ?>
                        <optgroup label="🔴 กิจกรรมที่สิ้นสุดแล้ว">
                            <?php foreach ($closed_events as $ev): ?>
                            <option value="<?= $ev['event_id'] ?>"
                                    data-year="<?= date('Y', strtotime($ev['event_date'])) ?>"
                                    data-ev-status="closed"
                                    data-group="closed">
                                <?= htmlspecialchars($ev['event_name']) ?> (<?= date('Y', strtotime($ev['event_date'])) ?>) — สิ้นสุดแล้ว
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>

                               

                                <?php if (empty($ongoing_events) && empty($closed_events)): ?>
                                <option value="" disabled>ไม่มีกิจกรรมในระบบ</option>
                                <?php endif; ?>
                            </select>

                            <div id="selectedStatus" style="display:none; margin-top: 10px;"></div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">หัวข้อข่าวสาร</label>
                            <input type="text" name="title" class="form-control" required placeholder="เช่น สรุปภาพบรรยากาศกิจกรรม...">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">รายละเอียดเนื้อหา</label>
                        <textarea name="detail" class="form-control" rows="5" placeholder="อธิบายรายละเอียดข่าวที่ต้องการสื่อสาร..."></textarea>
                    </div>

                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label text-primary fw-bold mb-0"><i class="fas fa-images me-2"></i>คลังภาพกิจกรรม</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMoreImage()">
                                <i class="fas fa-plus me-1"></i> เพิ่มรูปภาพ
                            </button>
                        </div>
                        <div class="preview-grid" id="image-container">
                            <div class="preview-item">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted" id="icon-0"></i>
                                <img id="img-0" src="" style="display:none; width:100%; height:100px; object-fit:cover;">
                                <input type="file" name="images[]" class="form-control form-control-sm mt-2" accept="image/*" onchange="preview(this, 0)">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <button type="submit" class="btn btn-main w-100 fw-bold">
                                <i class="fas fa-paper-plane me-2"></i> บันทึกและเผยแพร่ข่าว
                            </button>
                        </div>
                        <div class="col-md-4">
                            <a href="manage_news.php" class="btn btn-outline-secondary w-100" style="border-radius: 12px; padding: 15px;">ยกเลิก</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// 1. แก้ไขการสร้าง Array ข้อมูลกิจกรรมให้รองรับคำว่า "ปิดชั่วคราว"
const allOptions = <?= json_encode(array_map(function($ev) {
    $rawStatus = strtolower($ev['status']);
    $suffix = ' — กำลังดำเนินอยู่';
    if ($rawStatus === 'closed') $suffix = ' — สิ้นสุดแล้ว';
    if ($rawStatus === 'inactive') $suffix = ' — ปิดชั่วคราว';

    return [
        'id'       => $ev['event_id'],
        'text'     => htmlspecialchars($ev['event_name'], ENT_QUOTES)
                      . ' (' . date('Y', strtotime($ev['event_date'])) . ')'
                      . $suffix,
        'year'     => date('Y', strtotime($ev['event_date'])),
        'evStatus' => $rawStatus,
        'group'    => $rawStatus === 'closed' ? 'closed' : 'ongoing',
    ];
}, array_merge($ongoing_events, $closed_events))) ?>;

$('#eventSelect').select2({
    placeholder: '🔍 พิมพ์ชื่อกิจกรรมเพื่อค้นหา...',
    allowClear: true,
    width: '100%',
    language: {
        noResults: () => 'ไม่พบกิจกรรมที่ค้นหา',
        searching: () => 'กำลังค้นหา...',
    }
});

// 2. ปรับการตั้งค่าสีและข้อความของ Badge
const statusConfig = {
    active:   { cls: 'open',    icon: 'fa-circle-check',       text: 'เปิดอยู่' },
    inactive: { cls: 'warning', icon: 'fa-circle-exclamation', text: 'ปิดชั่วคราว' },
    closed:   { cls: 'closed',  icon: 'fa-circle-xmark',       text: 'สิ้นสุดแล้ว' },
};

$('#eventSelect').on('select2:select', function(e) {
    const opt    = e.params.data.element;
    const evStat = $(opt).data('ev-status') || 'active';
    const cfg    = statusConfig[evStat] || statusConfig.active;
    const box    = document.getElementById('selectedStatus');
    
    box.innerHTML = `<span class="status-badge-box ${cfg.cls}"><i class="fas ${cfg.icon}" style="font-size:0.65rem"></i> ${cfg.text}</span>`;
    box.style.display = 'block';
});

$('#eventSelect').on('select2:clear', function() {
    document.getElementById('selectedStatus').style.display = 'none';
});

// 3. แก้ไขฟังก์ชัน Filter ให้ส่งค่า evStatus เข้าไปใน Dataset ใหม่ทุกครั้ง
function filterByYear(year) {
    $('#eventSelect').val(null).trigger('change');
    document.getElementById('selectedStatus').style.display = 'none';

    const filtered = year === 'all' ? allOptions : allOptions.filter(o => o.year === year);
    const ongoing  = filtered.filter(o => o.group === 'ongoing');
    const closed   = filtered.filter(o => o.group === 'closed');

    const sel = document.getElementById('eventSelect');
    sel.innerHTML = '<option value="">-- พิมพ์เพื่อค้นหากิจกรรม --</option>';

    if (ongoing.length > 0) {
        const og = document.createElement('optgroup');
        og.label = '🟢 กิจกรรมที่กำลังดำเนินอยู่ / ปิดชั่วคราว';
        ongoing.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.text;
            opt.dataset.group    = 'ongoing';
            opt.dataset.evStatus = o.evStatus; // ส่งสถานะจริง (active/inactive)
            og.appendChild(opt);
        });
        sel.appendChild(og);
    }

    if (closed.length > 0) {
        const cg = document.createElement('optgroup');
        cg.label = '🔴 กิจกรรมที่สิ้นสุดแล้ว';
        closed.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.text;
            opt.dataset.group    = 'closed';
            opt.dataset.evStatus = 'closed';
            cg.appendChild(opt);
        });
        sel.appendChild(cg);
    }

    if (ongoing.length === 0 && closed.length === 0) {
        const opt = document.createElement('option');
        opt.disabled    = true;
        opt.textContent = 'ไม่มีกิจกรรมในปีที่เลือก';
        sel.appendChild(opt);
    }

    $('#eventSelect').trigger('change.select2');
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($status) && $status === "success"): ?>
        Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', text: 'ข่าวประชาสัมพันธ์ถูกเผยแพร่แล้ว', confirmButtonColor: '#1e293b' })
           .then(() => { window.location.href = 'manage_news.php'; });
    <?php elseif (isset($status) && $status === "error"): ?>
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่' });
    <?php endif; ?>
});

let imageCount = 1;
function addMoreImage() {
    const container = document.getElementById('image-container');
    const div = document.createElement('div');
    div.className = 'preview-item';
    div.innerHTML = `
        <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted" id="icon-${imageCount}"></i>
        <img id="img-${imageCount}" src="" style="display:none; width:100%; height:100px; object-fit:cover;">
        <input type="file" name="images[]" class="form-control form-control-sm mt-2"
               accept="image/*" onchange="preview(this, ${imageCount})">
    `;
    container.appendChild(div);
    imageCount++;
}

function preview(input, index) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('img-' + index).src = e.target.result;
            document.getElementById('img-' + index).style.display = 'block';
            document.getElementById('icon-' + index).style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>