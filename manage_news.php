<?php
require 'auth.php';
checkRole(['admin']);

$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$sql_pr = "SELECT pr.*, e.event_name, e.event_date, e.status AS event_status
           FROM news_update pr
           LEFT JOIN events e ON pr.event_id = e.event_id
           WHERE pr.status = 1
           GROUP BY pr.news_id
           ORDER BY pr.news_id DESC";
$result_pr = $conn->query($sql_pr);

$all_cards = [];
$years_set = [];

if ($result_pr && $result_pr->num_rows > 0) {
    while ($row = $result_pr->fetch_assoc()) {
        $cid    = (int)$row['news_id'];
        $evstat = strtolower(trim($row['event_status'] ?? 'active'));
        $year   = !empty($row['event_date']) ? date('Y', strtotime($row['event_date'])) : '0';
        if ($year !== '0') $years_set[$year] = true;

        $sql_imgs = "SELECT image_path FROM news_images WHERE news_id = $cid";
        $res_imgs = $conn->query($sql_imgs);
        $img_list = [];
        while ($r = $res_imgs->fetch_assoc()) { $img_list[] = 'img/' . $r['image_path']; }

        $all_cards[] = [
            'id'           => $cid,
            'title'        => $row['title'],
            'detail'       => $row['detail'],
            'event_name'   => $row['event_name'] ?? 'กิจกรรมทั่วไป',
            'event_date'   => $row['event_date'] ?? '',
            'event_year'   => $year,
            'event_status' => $evstat,
            'images'       => $img_list,
        ];
    }
}

$years = array_keys($years_set);
rsort($years);

// ✅ ปรับ Badge ให้มี 3 สถานะ (เพิ่มสีส้มสำหรับ Inactive)
function statusBadge($s) {
    $s = strtolower(trim($s));
    $map = [
        'active'   => ['bg'=>'#e6f4ea','color'=>'#1e7e34','dot'=>'#28a745','text'=>'เปิดอยู่'],
        'inactive' => ['bg'=>'#fff7ed','color'=>'#9a3412','dot'=>'#ea580c','text'=>'ปิดชั่วคราว'], // สีส้ม
        'closed'   => ['bg'=>'#f0f2f5','color'=>'#65676b','dot'=>'#8a8d91','text'=>'สิ้นสุดแล้ว'],
    ];
    $c = $map[$s] ?? $map['active'];
    $bg=$c['bg']; $color=$c['color']; $dot=$c['dot']; $text=$c['text'];
    return '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 12px;border-radius:50px;font-size:11px;font-weight:700;background:'.$bg.';color:'.$color.';">'
         . '<span style="width:6px;height:6px;border-radius:50%;background:'.$dot.';display:inline-block;"></span>'
         . $text.'</span>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข่าวประชาสัมพันธ์ - แอดมิน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&family=Sarabun:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00507b;
            --accent-color: #c45b00;
            --text-dark: #2c3e50;
            --text-muted: #475569;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        
        body { background-color: #f8fafc; font-family: 'IBM Plex Sans Thai', sans-serif; color: var(--text-dark); margin: 0; }
        .container { max-width: 1100px; margin-right: 30px; margin-left: auto; padding: 40px 20px; }
        .btn-action-main { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; text-decoration: none; border-radius: 15px; font-weight: 600; font-size: 15px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .btn-add { background: var(--primary-color); color: white; }
        .btn-add:hover { background: #003d5d; transform: translateY(-3px); }
        .btn-back { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .btn-back:hover { background: #f1f5f9; }

        .filter-bar { display: flex; align-items: center; justify-content: flex-end; gap: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 12px 20px; margin-bottom: 35px; flex-wrap: wrap; box-shadow: var(--shadow); }
        .filter-wrapper { position: relative; }
        .filter-toggle-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 16px; border-radius: 50px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'IBM Plex Sans Thai', sans-serif; transition: 0.2s; }
        .filter-toggle-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .filter-toggle-btn .chevron { transition: transform 0.2s; font-size: 10px; }
        .filter-toggle-btn.open .chevron { transform: rotate(180deg); }
        .filter-count-badge { background: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 99px; display: none; }
        .filter-panel { display: none; position: absolute; top: calc(100% + 8px); right: 0; z-index: 200; background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 0; min-width: 280px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); overflow: hidden; }
        .filter-panel.show { display: block; }
        .filter-panel-title { font-size: 17px; font-weight: 700; color: #1c1e21; padding: 16px 16px 12px; border-bottom: 1px solid #e4e6ea; }
        .filter-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-bottom: 1px solid #f0f2f5; }
        .filter-row-label { font-size: 14px; color: #1c1e21; font-weight: 500; }
        .fb-select { appearance: none; -webkit-appearance: none; padding: 6px 30px 6px 12px; border-radius: 8px; border: 1px solid #ccd0d5; background: #f0f2f5 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2365676b'/%3E%3C/svg%3E") no-repeat right 10px center; font-size: 13px; font-weight: 600; color: #1c1e21; cursor: pointer; font-family: 'IBM Plex Sans Thai', sans-serif; min-width: 130px; }
        .fb-select:focus { outline: none; border-color: #1877f2; }
        .filter-panel-footer { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; }
        .btn-clear { background: none; border: none; font-size: 14px; color: #1877f2; font-weight: 600; cursor: pointer; font-family: 'IBM Plex Sans Thai', sans-serif; padding: 0; }
        .btn-clear:hover { text-decoration: underline; }
        .btn-apply { padding: 8px 20px; border-radius: 8px; border: none; background: #1877f2; color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; font-family: 'IBM Plex Sans Thai', sans-serif; }
        .btn-apply:hover { background: #166fe5; }
        .active-tags { display: inline-flex; flex-wrap: wrap; gap: 6px; }
        .tag-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px 4px 12px; border-radius: 99px; background: #f1f5f9; border: 1px solid #e2e8f0; font-size: 12px; color: #475569; }
        .tag-chip button { background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 15px; line-height: 1; padding: 0; }
        .tag-chip button:hover { color: #ef4444; }

        .group-header { font-family: 'Sarabun', sans-serif; font-size: 1.5rem; color: #152d6d; margin: 50px 0 25px; display: flex; align-items: center; gap: 12px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
        .group-header.closed-group { color: #64748b; }
        .card-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 28px; margin-bottom: 40px; }
        .news-card { background: #fff; border-radius: 8px; border: 1px solid #e8ecf0; box-shadow: var(--shadow); display: flex; flex-direction: column; transition: transform 0.25s, box-shadow 0.25s; overflow: hidden; min-width: 0; }
        .news-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.09); }
        .news-card.hidden { display: none !important; }
        .image-gallery { display: grid; grid-template-columns: repeat(2,1fr); gap: 3px; cursor: pointer; }
        .image-gallery img { width: 100%; height: 120px; object-fit: cover; display: block; }
        .gallery-3plus img:first-child { grid-column: span 2; height: 190px; }
        .gallery-single img { grid-column: span 2; height: 220px; }
        .more-photos-overlay { position: relative; height: 120px; }
        .more-photos-overlay::after { content: attr(data-count); position: absolute; inset: 0; background: rgba(0,0,0,0.55); color: white; font-size: 20px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .card-body { padding: 18px 20px 20px; display: flex; flex-direction: column; flex: 1; }
        .card-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 6px; }
        .event-tag { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .card-body h3 { color: #92400e; font-size: 17px; font-family: 'Sarabun'; margin: 0 0 8px; line-height: 1.45; min-height: 50px; }
        .card-body p { color: #64748b; font-size: 13px; line-height: 1.6; flex: 1; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; margin: 0 0 16px; }
        .admin-controls { display: flex; gap: 8px; padding-top: 14px; border-top: 1px dashed #e2e8f0; }
        .btn-edit { flex: 1; background: #dcfce7; color: #166534; padding: 9px; border-radius: 10px; text-decoration: none; text-align: center; font-size: 13px; font-weight: 600; }
        .btn-edit:hover { background: #bbf7d0; }
        .btn-delete { flex: 1; background: #fee2e2; color: #991b1b; padding: 9px; border-radius: 10px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-delete:hover { background: #fecaca; }
        .no-results { text-align: center; padding: 50px 20px; color: #94a3b8; display: none; grid-column: 1/-1; }
        .no-results i { font-size: 2.5rem; margin-bottom: 12px; display: block; }
        .modal { display: none; position: fixed; z-index: 9999; inset: 0; background: rgba(15,23,42,0.92); overflow-y: auto; padding: 50px 20px; }
        .modal-container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 24px; padding: 30px; }
        .modal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 14px; }
        .modal-grid img { width: 100%; height: 260px; object-fit: cover; border-radius: 12px; }
        .close-modal { position: fixed; top: 22px; right: 32px; color: white; font-size: 34px; cursor: pointer; line-height: 1; }
        @media (max-width: 1024px) { .card-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 640px)  { .card-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include 'menu_admin.php'; ?>

<div class="container">
    <div class="header-box" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;">
        <div>
            <h2 style="font-size:1.6rem;font-family:'Sarabun',sans-serif;color:#152d6d;margin:0 0 4px;">จัดการข่าวประชาสัมพันธ์</h2>
            <p style="margin:0;font-size:13px;color:#94a3b8;">ทั้งหมด <?php echo count($all_cards); ?> รายการ</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="index.php" class="btn-action-main btn-back" style="padding:8px 16px;font-size:13px;"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
            <a href="add_news.php" class="btn-action-main btn-add" style="padding:8px 18px;font-size:13px;"><i class="fas fa-plus-circle"></i> เพิ่มข่าวสารใหม่</a>
        </div>
    </div>

    <div class="filter-bar">
        <div class="filter-wrapper">
            <button class="filter-toggle-btn" id="filterToggle" onclick="toggleFilterPanel()">
                <i class="fas fa-sliders-h" style="font-size:13px;"></i> ตัวกรอง
                <span class="filter-count-badge" id="filterCountBadge"></span>
                <span class="chevron">&#9660;</span>
            </button>

            <div class="filter-panel" id="filterPanel">
                <div class="filter-panel-title">ตัวกรอง</div>
                <div class="filter-row">
                    <span class="filter-row-label">ปีที่:</span>
                    <select class="fb-select" id="yearSelect" onchange="pending.year=this.value">
                        <option value="all">ทั้งหมด</option>
                        <?php foreach ($years as $y): if ($y === '0') continue; ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-row">
                    <span class="filter-row-label">สถานะ:</span>
                    <select class="fb-select" id="statusSelect" onchange="pending.status=this.value">
                        <option value="all">ทั้งหมด</option>
                        <option value="active">เปิดอยู่</option>
                        <option value="inactive">ปิดชั่วคราว</option>
                        <option value="closed">สิ้นสุดแล้ว</option>
                    </select>
                </div>

                <div class="filter-panel-footer">
                    <button class="btn-clear" onclick="clearFilter()">ล้างตัวกรอง</button>
                    <button class="btn-apply" onclick="applyFilter()">นำไปใช้</button>
                </div>
            </div>
        </div>
        <div class="active-tags" id="activeTags"></div>
    </div>

    <div class="group-header" id="groupActive">
        <i class="fas fa-circle-play" style="color:#28a745;"></i> กิจกรรมที่เปิดอยู่
    </div>
    <div class="card-grid" id="gridActive">
        <?php foreach ($all_cards as $c): if ($c['event_status'] !== 'active') continue; ?>
        <div class="news-card" data-year="<?= $c['event_year'] ?>" data-status="active">
            <div class="image-gallery <?= count($c['images']) >= 3 ? 'gallery-3plus' : (count($c['images']) == 2 ? 'gallery-2' : 'gallery-single') ?>" onclick='openPhotoModal(<?= json_encode($c['images']) ?>)'>
                <?php if (!empty($c['images'])): ?>
                    <?php foreach (array_slice($c['images'], 0, 5) as $i => $src): ?>
                        <?php if ($i === 4 && count($c['images']) > 5): ?>
                            <div class="more-photos-overlay" data-count="+<?= count($c['images']) - 4 ?>"><img src="<?= $src ?>"></div>
                        <?php else: ?>
                            <img src="<?= $src ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <img src="img/default.jpg">
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="card-meta">
                    <span class="event-tag"><i class="fas fa-tag"></i> <?= htmlspecialchars($c['event_name']) ?></span>
                    <?= statusBadge('active') ?>
                </div>
                <h3><?= htmlspecialchars($c['title']) ?></h3>
                <p><?= htmlspecialchars($c['detail']) ?></p>
                <div class="admin-controls">
                    <a href="edit_news.php?id=<?= $c['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> แก้ไข</a>
                    <button class="btn-delete" onclick="confirmDelete(<?= $c['id'] ?>)"><i class="fas fa-trash-alt"></i> ลบ</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="no-results" id="noActive"><i class="fas fa-search"></i>ไม่พบข้อมูล</div>
    </div>

    <div class="group-header" id="groupInactive">
        <i class="fas fa-circle-pause" style="color:#ea580c;"></i> กิจกรรมที่ปิดชั่วคราว
    </div>
    <div class="card-grid" id="gridInactive">
        <?php foreach ($all_cards as $c): if ($c['event_status'] !== 'inactive') continue; ?>
        <div class="news-card" data-year="<?= $c['event_year'] ?>" data-status="inactive">
            <div class="image-gallery <?= count($c['images']) >= 3 ? 'gallery-3plus' : (count($c['images']) == 2 ? 'gallery-2' : 'gallery-single') ?>" onclick='openPhotoModal(<?= json_encode($c['images']) ?>)'>
                <?php if (!empty($c['images'])): ?>
                    <?php foreach (array_slice($c['images'], 0, 5) as $i => $src): ?>
                        <?php if ($i === 4 && count($c['images']) > 5): ?>
                            <div class="more-photos-overlay" data-count="+<?= count($c['images']) - 4 ?>"><img src="<?= $src ?>"></div>
                        <?php else: ?>
                            <img src="<?= $src ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <img src="img/default.jpg">
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="card-meta">
                    <span class="event-tag"><i class="fas fa-tag"></i> <?= htmlspecialchars($c['event_name']) ?></span>
                    <?= statusBadge('inactive') ?>
                </div>
                <h3><?= htmlspecialchars($c['title']) ?></h3>
                <p><?= htmlspecialchars($c['detail']) ?></p>
                <div class="admin-controls">
                    <a href="edit_news.php?id=<?= $c['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> แก้ไข</a>
                    <button class="btn-delete" onclick="confirmDelete(<?= $c['id'] ?>)"><i class="fas fa-trash-alt"></i> ลบ</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="no-results" id="noInactive"><i class="fas fa-search"></i>ไม่พบข้อมูล</div>
    </div>

    <div class="group-header closed-group" id="groupClosed">
        <i class="fas fa-circle-stop"></i> กิจกรรมที่สิ้นสุดแล้ว
    </div>
    <div class="card-grid" id="gridClosed">
        <?php foreach ($all_cards as $c): if ($c['event_status'] !== 'closed') continue; ?>
        <div class="news-card" data-year="<?= $c['event_year'] ?>" data-status="closed">
            <div class="image-gallery <?= count($c['images']) >= 3 ? 'gallery-3plus' : (count($c['images']) == 2 ? 'gallery-2' : 'gallery-single') ?>" onclick='openPhotoModal(<?= json_encode($c['images']) ?>)'>
                <?php if (!empty($c['images'])): ?>
                    <?php foreach (array_slice($c['images'], 0, 5) as $i => $src): ?>
                        <?php if ($i === 4 && count($c['images']) > 5): ?>
                            <div class="more-photos-overlay" data-count="+<?= count($c['images']) - 4 ?>"><img src="<?= $src ?>"></div>
                        <?php else: ?>
                            <img src="<?= $src ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <img src="img/default.jpg">
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="card-meta">
                    <span class="event-tag"><i class="fas fa-tag"></i> <?= htmlspecialchars($c['event_name']) ?></span>
                    <?= statusBadge('closed') ?>
                </div>
                <h3><?= htmlspecialchars($c['title']) ?></h3>
                <p><?= htmlspecialchars($c['detail']) ?></p>
                <div class="admin-controls">
                    <a href="edit_news.php?id=<?= $c['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> แก้ไข</a>
                    <button class="btn-delete" onclick="confirmDelete(<?= $c['id'] ?>)"><i class="fas fa-trash-alt"></i> ลบ</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="no-results" id="noClosed"><i class="fas fa-search"></i>ไม่พบข้อมูล</div>
    </div>
</div>

<div id="photoModal" class="modal">
    <span class="close-modal" onclick="closePhotoModal()">&times;</span>
    <div class="modal-container">
        <h2 style="text-align:center;font-family:'Sarabun';margin-bottom:22px;">รูปภาพกิจกรรมทั้งหมด</h2>
        <div id="modalGrid" class="modal-grid"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let pending = { year: 'all', status: 'all' };
let applied = { year: 'all', status: 'all' };

const statusLabels = { active:'เปิดอยู่', inactive:'ปิดชั่วคราว', closed:'สิ้นสุดแล้ว' };

function toggleFilterPanel() {
    const panel  = document.getElementById('filterPanel');
    const toggle = document.getElementById('filterToggle');
    const open   = panel.classList.contains('show');
    panel.classList.toggle('show', !open);
    toggle.classList.toggle('open', !open);
    if (!open) {
        pending = {...applied};
        document.getElementById('yearSelect').value   = pending.year;
        document.getElementById('statusSelect').value = pending.status;
    }
}

function clearFilter() {
    pending = { year:'all', status:'all' };
    document.getElementById('yearSelect').value   = 'all';
    document.getElementById('statusSelect').value = 'all';
}

function applyFilter() {
    applied = {...pending};
    document.getElementById('filterPanel').classList.remove('show');
    document.getElementById('filterToggle').classList.remove('open');
    runFilter(); renderTags(); updateBadge();
}

function runFilter() {
    const { year, status } = applied;
    const grids = [
        {id: 'Active', val: 'active'},
        {id: 'Inactive', val: 'inactive'},
        {id: 'Closed', val: 'closed'}
    ];

    grids.forEach(g => {
        const header = document.getElementById('group' + g.id);
        const grid   = document.getElementById('grid' + g.id);
        const isStatusMatch = (status === 'all' || status === g.val);

        if (isStatusMatch) {
            header.style.display = '';
            grid.style.display = '';
            let vis = 0;
            const cards = grid.querySelectorAll('.news-card');
            cards.forEach(c => {
                const isYearMatch = (year === 'all' || c.dataset.year === year);
                c.classList.toggle('hidden', !isYearMatch);
                if (isYearMatch) vis++;
            });
            const noMsg = grid.querySelector('.no-results');
            if (noMsg) noMsg.style.display = vis === 0 ? 'block' : 'none';
        } else {
            header.style.display = 'none';
            grid.style.display = 'none';
        }
    });
}

function renderTags() {
    const box = document.getElementById('activeTags');
    box.innerHTML = '';
    const labelMap = {
        year:   v => v !== 'all' ? `${v}` : null,
        status: v => v !== 'all' ? (statusLabels[v] ?? null) : null,
    };
    Object.entries(applied).forEach(([g, v]) => {
        const lbl = labelMap[g](v);
        if (!lbl) return;
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = `${lbl} <button onclick="removeTag('${g}')">×</button>`;
        box.appendChild(chip);
    });
}

function removeTag(group) {
    applied[group] = 'all'; pending[group] = 'all';
    document.getElementById(group === 'year' ? 'yearSelect' : 'statusSelect').value = 'all';
    runFilter(); renderTags(); updateBadge();
}

function updateBadge() {
    const n  = Object.values(applied).filter(v => v !== 'all').length;
    const el = document.getElementById('filterCountBadge');
    el.style.display = n ? 'inline' : 'none';
    el.textContent   = n;
}

document.addEventListener('click', e => {
    const panel = document.getElementById('filterPanel');
    const btn   = document.getElementById('filterToggle');
    if (panel && panel.classList.contains('show') && !panel.contains(e.target) && !btn.contains(e.target)) {
        panel.classList.remove('show');
        btn.classList.remove('open');
    }
});

function openPhotoModal(images) {
    if (!images || !images.length) return;
    const grid = document.getElementById('modalGrid');
    grid.innerHTML = '';
    images.forEach(src => { grid.innerHTML += `<div><img src="${src}"></div>`; });
    document.getElementById('photoModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closePhotoModal() {
    document.getElementById('photoModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function confirmDelete(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?', text: 'ข้อมูลและรูปภาพจะหายไปถาวร',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonText: 'ยกเลิก', confirmButtonText: 'ลบเลย'
    }).then(r => { if (r.isConfirmed) window.location.href = 'delete_news.php?id=' + id; });
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>