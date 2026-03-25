<?php
session_start();
include('connect.php');

$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$user_data = null;
if (isset($_SESSION['user_id'])) {
    $user_pk_id = $_SESSION['user_id'];
    $stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_pk_id);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
}

$sql_pr = "SELECT pr.*, e.event_name, e.event_date, e.status AS event_status
           FROM news_update pr
           LEFT JOIN events e ON pr.event_id = e.event_id
           WHERE pr.status = 1
           ORDER BY pr.news_id DESC";
$result_pr = $conn->query($sql_pr);

$all_news   = [];
$years_set  = [];

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

        $all_news[] = [
            'id'           => $cid,
            'title'        => $row['title'],
            'detail'       => $row['detail'],
            'event_name'   => $row['event_name'] ?? 'ข่าวสารทั่วไป',
            'event_date'   => $row['event_date'] ?? '',
            'event_year'   => $year,
            'event_status' => $evstat,
            'images'       => $img_list,
        ];
    }
}

$years = array_keys($years_set);
rsort($years);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ประชาสัมพันธ์กิจกรรมใจดี</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600&family=Sarabun:wght@700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-blue: #07164c;
        --secondary-gold: #cf6800;
        --bg-soft: #f4f7fa;
        --white: #ffffff;
        --surface: rgba(255,255,255,0.9);
        --text-dark: #0f172a;
        --text-slate: #475569;
        --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.1);
        --shadow-lg: 0 20px 40px -5px rgba(7,22,76,0.1);
        --shadow-hover: 0 30px 60px -12px rgba(7,22,76,0.2);
        --radius-xl: 32px;
    }
    body { background-color: var(--bg-soft); font-family: 'Anuphan', sans-serif; color: var(--text-dark); margin: 0; overflow-x: hidden; }

    .bg-blobs { position: fixed; inset: 0; z-index: -1; overflow: hidden; }
    .blob { position: absolute; width: 600px; height: 600px; filter: blur(80px); opacity: 0.15; border-radius: 50%; animation: float 20s infinite alternate; }
    .blob-1 { background: #07164c; top: -100px; right: -100px; }
    .blob-2 { background: #cf6800; bottom: -100px; left: -100px; animation-delay: -5s; }
    @keyframes float { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(100px,50px) scale(1.1); } }

    .container { max-width: 1200px; margin: 0 auto; padding: 60px 24px; }

    .hero-header { text-align: center; margin-bottom: 48px; position: relative; }
    .hero-header h1 { font-family: 'Sarabun', sans-serif; font-size: clamp(2.5rem, 5vw, 3.5rem); font-weight: 800; color: var(--primary-blue); margin-bottom: 15px; letter-spacing: -0.02em; }
    .accent-line { width: 120px; height: 8px; background: linear-gradient(90deg, var(--secondary-gold), #ff8c00); margin: 0 auto 30px; border-radius: 50px; }
    .hero-header p { color: var(--text-slate); font-size: 1.15rem; max-width: 600px; margin: 0 auto 40px; }
    .btn-action { display: inline-flex; align-items: center; gap: 12px; padding: 14px 28px; text-decoration: none; color: #64748b; background: var(--white); border-radius: 50px; font-weight: 700; font-size: 0.95rem; box-shadow: var(--shadow-sm); transition: 0.4s cubic-bezier(0.175,0.885,0.32,1.275); border: 1px solid rgba(0,0,0,0.02); }
    .btn-action:hover { transform: translateY(-5px) scale(1.02); color: var(--secondary-gold); box-shadow: var(--shadow-lg); }

    /* Filter Bar */
    .filter-bar { display: flex; align-items: center; justify-content: flex-end; gap: 8px; background: var(--white); border-radius: 16px; padding: 10px 18px; margin-bottom: 40px; box-shadow: var(--shadow-sm); }
    .filter-wrap { position: relative; }
    .filter-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; border: 1px solid #dddfe2; background: #f0f2f5; color: #1c1e21; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Anuphan', sans-serif; transition: 0.15s; }
    .filter-btn:hover { background: #e4e6ea; }
    .filter-btn .chev { transition: transform 0.2s; font-size: 9px; }
    .filter-btn.open .chev { transform: rotate(180deg); }
    .f-badge { background: var(--secondary-gold); color: #fff; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 99px; display: none; }
    .f-panel { display: none; position: absolute; top: calc(100% + 6px); right: 0; z-index: 300; background: var(--white); border: 1px solid #dddfe2; border-radius: 12px; min-width: 270px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); overflow: hidden; font-family: 'Anuphan', sans-serif; }
    .f-panel.show { display: block; }
    .f-head { font-size: 16px; font-weight: 700; color: #1c1e21; padding: 14px 16px 10px; border-bottom: 1px solid #e4e6ea; font-family: 'Sarabun', sans-serif; }
    .f-row { display: flex; align-items: center; justify-content: space-between; padding: 9px 16px; border-bottom: 1px solid #f0f2f5; }
    .f-row-label { font-size: 14px; color: #1c1e21; font-weight: 500; }
    .fb-sel { appearance: none; -webkit-appearance: none; padding: 5px 28px 5px 11px; border-radius: 8px; border: 1px solid #ccd0d5; background: #f0f2f5 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2365676b'/%3E%3C/svg%3E") no-repeat right 9px center; font-size: 13px; font-weight: 600; color: #1c1e21; cursor: pointer; font-family: 'Anuphan', sans-serif; min-width: 130px; }
    .fb-sel:focus { outline: none; border-color: var(--secondary-gold); }
    .f-footer { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; }
    .btn-clr { background: none; border: none; font-size: 13px; color: var(--secondary-gold); font-weight: 600; cursor: pointer; font-family: 'Anuphan', sans-serif; padding: 0; }
    .btn-clr:hover { text-decoration: underline; }
    .btn-ok { padding: 7px 18px; border-radius: 8px; border: none; background: var(--primary-blue); color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; font-family: 'Anuphan', sans-serif; }
    .btn-ok:hover { background: #0e2266; }
    .tags { display: inline-flex; flex-wrap: wrap; gap: 6px; }
    .t-chip { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px 3px 10px; border-radius: 99px; background: #fff7ed; border: 1px solid #fed7aa; font-size: 12px; color: #92400e; font-weight: 600; }
    .t-chip button { background: none; border: none; cursor: pointer; color: #92400e; font-size: 14px; line-height: 1; padding: 0; opacity: 0.6; }
    .t-chip button:hover { opacity: 1; }

    /* Section */
    .section-meta { display: flex; align-items: center; gap: 20px; margin-bottom: 35px; padding-bottom: 15px; border-bottom: 2px solid rgba(7,22,76,0.05); }
    .section-icon { width: 54px; height: 54px; background: var(--white); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--secondary-gold); box-shadow: var(--shadow-sm); }
    .section-meta h2 { font-family: 'Sarabun', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--primary-blue); margin: 0; }
    .section-meta p { margin: 4px 0 0; color: var(--text-slate); font-size: 0.95rem; }

    /* ✅ Card Grid — 3 ช่องคงที่ */
    .card-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 28px;
    }

    .card { background: var(--surface); backdrop-filter: blur(12px); border-radius: var(--radius-xl); padding: 24px; box-shadow: var(--shadow-lg); display: flex; flex-direction: column; transition: all 0.6s cubic-bezier(0.23,1,0.32,1); position: relative; border: 1px solid rgba(255,255,255,0.5); min-width: 0; }
    .card:hover { transform: translateY(-12px); box-shadow: var(--shadow-hover); border-color: rgba(207,104,0,0.2); }
    .card.hidden { display: none !important; }

    .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; margin-bottom: 14px; }
    .pill-active   { background: #e6f4ea; color: #1e7e34; }
    .pill-inactive { background: #fff7ed; color: #9a3412; }
    .pill-closed   { background: #f1f3f4; color: #5f6368; }

    .gallery-slider { position: relative; overflow: hidden; border-radius: 22px; margin-bottom: 24px; height: 200px; background: #e2e8f0; }
    .slider-track { display: flex; transition: transform 0.5s ease; height: 100%; }
    .slide { min-width: 100%; height: 100%; }
    .slide img { width: 100%; height: 100%; object-fit: cover; }
    .nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; font-size: 16px; padding: 7px 11px; cursor: pointer; border-radius: 50%; z-index: 10; }
    .nav.prev { left: 8px; }
    .nav.next { right: 8px; }
    .nav:hover { background: rgba(0,0,0,0.8); }

    .card-body { flex-grow: 1; }
    .tag-bubble { display: inline-flex; align-items: center; gap: 6px; background: #fff7ed; color: var(--secondary-gold); padding: 6px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; margin-bottom: 10px; }
    .card-body h3 { font-size: 1.2rem; font-weight: 800; margin: 0 0 10px; line-height: 1.35; color: var(--primary-blue); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .card-body p { font-size: 0.9rem; color: var(--text-slate); line-height: 1.6; margin-bottom: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    .btn-read-more {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 14px; padding: 9px 18px; border-radius: 50px;
        background: var(--primary-blue); color: #fff;
        font-size: 0.82rem; font-weight: 700; text-decoration: none;
        transition: 0.25s; border: none; cursor: pointer; font-family: 'Anuphan', sans-serif;
    }
    .btn-read-more:hover { background: #0e2266; transform: translateY(-2px); color: #fff; }
    .no-news { grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #94a3b8; display: none; }
    .no-news i { font-size: 2.5rem; margin-bottom: 14px; display: block; opacity: 0.4; }

    .modal { display: none; position: fixed; z-index: 10000; inset: 0; background: rgba(252,252,255,0.95); backdrop-filter: blur(10px); padding: 40px 20px; overflow-y: auto; scrollbar-width: none; }
    .modal::-webkit-scrollbar { display: none; }
    .modal-content { max-width: 1200px; margin: 40px auto; }
    .modal-title { text-align: center; margin-bottom: 40px; }
    .modal-title h2 { font-family: 'Sarabun'; font-size: 2rem; color: #0f0f13; }
    .modal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap: 20px; }
    .modal-grid img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); transition: 0.3s; }
    .modal-grid img:hover { transform: scale(1.03); }
    .close-btn { position: fixed; top: 30px; right: 30px; color: #333; font-size: 40px; cursor: pointer; z-index: 10001; transition: 0.3s; }
    .close-btn:hover { color: var(--secondary-gold); transform: rotate(90deg); }

    @media (max-width: 1024px) { .card-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 640px)  { .card-grid { grid-template-columns: 1fr; } .hero-header { margin-bottom: 32px; } .container { padding: 40px 16px; } }
</style>
</head>
<body>
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <?php include 'menu_volunteer.php'; ?>

<div class="container">

    <div class="hero-header">
        <h1>ประชาสัมพันธ์ข่าวสาร</h1>
        <div class="accent-line"></div>
        <p>เกาะติดทุกกิจกรรมจิตอาสา และโครงการต่างๆ เพื่อสร้างรอยยิ้มให้กับน้องๆ และชุมชน</p>
        <a href="index.php" class="btn-action"><i class="fas fa-chevron-left"></i> กลับไปหน้าหลัก</a>
    </div>

    <div class="filter-bar">
        <div class="filter-wrap">
            <button class="filter-btn" id="fBtn" onclick="toggleF()">
                <i class="fas fa-sliders-h" style="font-size:12px;"></i>
                ตัวกรอง
                <span class="f-badge" id="fBadge"></span>
                <span class="chev">&#9660;</span>
            </button>
            <div class="f-panel" id="fPanel">
                <div class="f-head">ตัวกรอง</div>
                <div class="f-row">
                    <span class="f-row-label">ปีที่:</span>
                    <select class="fb-sel" id="yearSel" onchange="pend.year=this.value">
                        <option value="all">ทั้งหมด</option>
                        <?php foreach ($years as $y): if ($y === '0') continue; ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="f-row">
                    <span class="f-row-label">สถานะ:</span>
                    <select class="fb-sel" id="statSel" onchange="pend.status=this.value">
                        <option value="all">ทั้งหมด</option>
                        <option value="active">เปิดรับบริจาคอยู่</option>
                        <option value="inactive">ปิดรับชั่วคราว</option>
                        <option value="closed">สิ้นสุดแล้ว</option>
                    </select>
                </div>
                <div class="f-footer">
                    <button class="btn-clr" onclick="clearF()">ล้างตัวกรอง</button>
                    <button class="btn-ok" onclick="applyF()">นำไปใช้</button>
                </div>
            </div>
        </div>
        <div class="tags" id="fTags"></div>
    </div>

    <div class="section-meta" id="secOngoing">
        <div class="section-icon"><i class="fas fa-fire"></i></div>
        <div>
            <h2>กิจกรรมที่กำลังดำเนินอยู่</h2>
            <p>รวมกิจกรรมที่เปิดรับและหยุดพักชั่วคราว (ยังไม่สิ้นสุด)</p>
        </div>
    </div>
    <div class="card-grid" id="gridOngoing" style="margin-bottom:80px;">
        <?php foreach ($all_news as $c):
            if ($c['event_status'] === 'closed') continue;
            $imgs = $c['images']; $n = count($imgs);
            
            // Logic เปลี่ยนสีและข้อความตามสถานะจริง
            if ($c['event_status'] === 'inactive') {
                $pillClass = 'pill-inactive'; $pillText = 'ปิดรับชั่วคราว'; $dot = '#ea580c';
            } else {
                $pillClass = 'pill-active'; $pillText = 'เปิดรับบริจาคอยู่'; $dot = '#28a745';
            }
        ?>
        <div class="card" data-year="<?= $c['event_year'] ?>" data-status="<?= $c['event_status'] ?>">
            <div class="gallery-slider" onclick='openPhotoModal(<?= json_encode($imgs) ?>)'>
                <div class="slider-track">
                    <?php if ($n > 0): foreach ($imgs as $s): ?>
                        <div class="slide"><img src="<?= $s ?>" loading="lazy"></div>
                    <?php endforeach; else: ?>
                        <div class="slide"><img src="img/default.jpg"></div>
                    <?php endif; ?>
                </div>
                <?php if ($n > 1): ?>
                <button class="nav prev" onclick="event.stopPropagation();slide(this,-1)">❮</button>
                <button class="nav next" onclick="event.stopPropagation();slide(this,1)">❯</button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <span class="tag-bubble"><i class="fas fa-bullhorn"></i><?= htmlspecialchars($c['event_name']) ?></span>
                <div>
                    <span class="status-pill <?= $pillClass ?>">
                        <span style="width:6px;height:6px;border-radius:50%;background:<?= $dot ?>;display:inline-block;"></span>
                        <?= $pillText ?>
                    </span>
                </div>
                <h3><?= htmlspecialchars($c['title']) ?></h3>
                <p><?= htmlspecialchars($c['detail']) ?></p>
                <a href="news_detail.php?id=<?= $c['id'] ?>" class="btn-read-more">
                    อ่านเพิ่มเติม <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="no-news" id="noOngoing"><i class="fas fa-search"></i>ไม่พบข่าวที่ตรงกับตัวกรอง</div>
    </div>

    <div class="section-meta" id="secClosed">
        <div class="section-icon" style="color:var(--primary-blue);"><i class="fas fa-check-circle"></i></div>
        <div>
            <h2>บันทึกความประทับใจ</h2>
            <p>ย้อนชมภาพกิจกรรมความสำเร็จที่พวกเราได้ทำร่วมกันมา</p>
        </div>
    </div>
    <div class="card-grid" id="gridClosed">
        <?php foreach ($all_news as $c):
            if ($c['event_status'] !== 'closed') continue;
            $imgs = $c['images']; $n = count($imgs);
        ?>
        <div class="card" data-year="<?= $c['event_year'] ?>" data-status="closed">
            <div class="gallery-slider" onclick='openPhotoModal(<?= json_encode($imgs) ?>)'>
                <div class="slider-track">
                    <?php if ($n > 0): foreach ($imgs as $s): ?>
                        <div class="slide"><img src="<?= $s ?>" loading="lazy"></div>
                    <?php endforeach; else: ?>
                        <div class="slide"><img src="img/default.jpg"></div>
                    <?php endif; ?>
                </div>
                <?php if ($n > 1): ?>
                <button class="nav prev" onclick="event.stopPropagation();slide(this,-1)">❮</button>
                <button class="nav next" onclick="event.stopPropagation();slide(this,1)">❯</button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <span class="tag-bubble"><i class="fas fa-bullhorn"></i><?= htmlspecialchars($c['event_name']) ?></span>
                <div>
                    <span class="status-pill pill-closed">
                        <span style="width:6px;height:6px;border-radius:50%;background:#9aa0a6;display:inline-block;"></span>
                        สิ้นสุดแล้ว
                    </span>
                </div>
                <h3><?= htmlspecialchars($c['title']) ?></h3>
                <p><?= htmlspecialchars($c['detail']) ?></p>
                <a href="news_detail.php?id=<?= $c['id'] ?>" class="btn-read-more">
                    อ่านเพิ่มเติม <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="no-news" id="noClosed"><i class="fas fa-search"></i>ไม่พบข่าวที่ตรงกับตัวกรอง</div>
    </div>

</div>

<div id="photoModal" class="modal">
    <span class="close-btn" onclick="closePhotoModal()">&times;</span>
    <div class="modal-content">
        <div class="modal-title">
            <h2>บรรยากาศกิจกรรม</h2>
            <div class="accent-line" style="width:60px;height:4px;"></div>
        </div>
        <div id="modalGrid" class="modal-grid"></div>
    </div>
</div>

<script>
let pend = {year:'all', status:'all'};
let appl = {year:'all', status:'all'};
const SL = {active:'เปิดรับบริจาคอยู่', inactive:'ปิดรับชั่วคราว', closed:'สิ้นสุดแล้ว'};

function toggleF() {
    const p=document.getElementById('fPanel'), b=document.getElementById('fBtn');
    const o=p.classList.contains('show');
    p.classList.toggle('show',!o); b.classList.toggle('open',!o);
    if (!o) { pend={...appl}; document.getElementById('yearSel').value=pend.year; document.getElementById('statSel').value=pend.status; }
}
function clearF() {
    pend={year:'all',status:'all'};
    document.getElementById('yearSel').value='all';
    document.getElementById('statSel').value='all';
}
function applyF() {
    appl={...pend};
    document.getElementById('fPanel').classList.remove('show');
    document.getElementById('fBtn').classList.remove('open');
    doFilter(); renderTags(); updateBadge();
}
function doFilter() {
    const {year,status}=appl;

    // กรองการแสดงผล Section
    const showOngoing = (status==='all' || status==='active' || status==='inactive');
    const showClosed  = (status==='all' || status==='closed');
    document.getElementById('secOngoing').style.display = showOngoing ? '' : 'none';
    document.getElementById('gridOngoing').style.display = showOngoing ? '' : 'none';
    document.getElementById('secClosed').style.display  = showClosed  ? '' : 'none';
    document.getElementById('gridClosed').style.display  = showClosed  ? '' : 'none';

    ['gridOngoing','gridClosed'].forEach(gid=>{
        const g=document.getElementById(gid), cards=g.querySelectorAll('.card');
        let vis=0;
        cards.forEach(c=>{
            const isYearMatch = (year==='all' || c.dataset.year===year);
            const isStatMatch = (status==='all' || status===c.dataset.status);
            const ok = isYearMatch && isStatMatch;
            c.classList.toggle('hidden',!ok); if(ok)vis++;
        });
        const nm=g.querySelector('.no-news');
        if(nm) nm.style.display=vis===0?'block':'none';
    });
}
function renderTags() {
    const box=document.getElementById('fTags'); box.innerHTML='';
    const lm={year:v=>v!=='all'?`${+v}`:null, status:v=>v!=='all'?SL[v]:null};
    Object.entries(appl).forEach(([g,v])=>{
        const l=lm[g](v); if(!l)return;
        const ch=document.createElement('span'); ch.className='t-chip';
        ch.innerHTML=`${l}<button onclick="rmTag('${g}')">×</button>`;
        box.appendChild(ch);
    });
}
function rmTag(g) {
    appl[g]='all'; pend[g]='all';
    document.getElementById(g==='year'?'yearSel':'statSel').value='all';
    doFilter(); renderTags(); updateBadge();
}
function updateBadge() {
    const n=Object.values(appl).filter(v=>v!=='all').length;
    const el=document.getElementById('fBadge');
    el.style.display=n?'inline':'none'; el.textContent=n;
}
document.addEventListener('click',e=>{
    const p=document.getElementById('fPanel'),b=document.getElementById('fBtn');
    if(p.classList.contains('show')&&!p.contains(e.target)&&!b.contains(e.target)){p.classList.remove('show');b.classList.remove('open');}
});

function slide(btn,dir) {
    const slider=btn.closest('.gallery-slider'), track=slider.querySelector('.slider-track');
    const slides=slider.querySelectorAll('.slide');
    let idx=parseInt(track.getAttribute('data-index')||0)+dir;
    if(idx<0)idx=slides.length-1;
    if(idx>=slides.length)idx=0;
    track.style.transform=`translateX(-${idx*100}%)`;
    track.setAttribute('data-index',idx);
}
function openPhotoModal(images) {
    if(!images||!images.length)return;
    document.getElementById('modalGrid').innerHTML=images.map(s=>`<img src="${s}">`).join('');
    document.getElementById('photoModal').style.display='block';
    document.body.style.overflow='hidden';
}
function closePhotoModal() {
    document.getElementById('photoModal').style.display='none';
    document.body.style.overflow='auto';
}
window.onclick=e=>{if(e.target===document.getElementById('photoModal'))closePhotoModal();};
document.addEventListener('DOMContentLoaded', doFilter);
</script>

<?php include 'footer.php'; ?>
</body>
</html>