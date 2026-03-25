<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php'); 

if (!isset($conn)) {
    die("Database connection variable '$conn' not found. Please check connect.php");
}

$conn->set_charset("utf8mb4");

// --- 1. ดึงสถิติจำนวนผู้ใช้งาน ---
$total_members = 0; 
$total_volunteers = 0;

$res_count = $conn->query("SELECT COUNT(*) as total_all FROM users");
if ($res_count) {
    $row = $res_count->fetch_assoc();
    $total_members = $row['total_all'] ?? 0;
}


// นับเฉพาะอาสาสมัครที่ "อนุมัติแล้ว"
$res_v_only = $conn->query("
    SELECT COUNT(*) as total 
    FROM users 
    WHERE role = 'volunteer' 
    AND status = 'active'
");

if ($res_v_only) {
    $row = $res_v_only->fetch_assoc();
    $total_volunteers = $row['total'] ?? 0;
} else {
    $total_volunteers = 0;
}

// --- 2. ฟังก์ชันจัดกลุ่มและกำหนดสี ---
function get_clean_group($cat_raw) {
    $cat = trim($cat_raw);
    if (mb_strpos($cat, 'ยาสามัญ') !== false) return 'ยาสามัญประจำบ้าน';
    if (mb_strpos($cat, 'เสื้อผ้า') !== false) return 'เสื้อผ้ามือสอง';
    if (mb_strpos($cat, 'ข้าวสาร') !== false || mb_strpos($cat, 'อาหารแห้ง') !== false) return 'ข้าวสารและอาหารแห้ง';
    if (mb_strpos($cat, 'เรียน') !== false) return 'อุปกรณ์การเรียน';
    if (mb_strpos($cat, 'กีฬา') !== false) return 'อุปกรณ์กีฬา';
    if (mb_strpos($cat, 'ของเล่น') !== false) return 'ของเล่นและตุ๊กตา';
    if (mb_strpos($cat, 'ทำความสะอาด') !== false) return 'ผลิตภัณฑ์ทำความสะอาด';
    if (mb_strpos($cat, 'ภายในบ้าน') !== false) return 'เครื่องใช้ภายในบ้าน';
    return trim(preg_replace('/^[0-9]+\.\s?/', '', $cat));
}

function getColor($name) {
    $map = [
        'ข้าวสารและอาหารแห้ง'   => '#1d70e2',
        'ยาสามัญประจำบ้าน'      => '#f9c20a',
        'เสื้อผ้ามือสอง'        => '#107c41',
        'อุปกรณ์การเรียน'       => '#d93025',
        'อุปกรณ์กีฬา'           => '#7a00e6',
        'ของเล่นและตุ๊กตา'      => '#7d4bc3',
        'ผลิตภัณฑ์ทำความสะอาด'  => '#f97316',
        'เครื่องใช้ภายในบ้าน'   => '#10b981',
    ];
    return $map[$name] ?? '#94a3b8';
}

// --- 3. ยอดรวม 8 Card ---
$cats_list = ['ข้าวสารและอาหารแห้ง','ยาสามัญประจำบ้าน','เสื้อผ้ามือสอง','อุปกรณ์การเรียน','อุปกรณ์กีฬา','ของเล่นและตุ๊กตา','ผลิตภัณฑ์ทำความสะอาด','เครื่องใช้ภายในบ้าน'];
$card_data = [];
foreach ($cats_list as $cl) { $card_data[$cl] = ['tar' => 0, 'rec' => 0]; }

/**
 * Logic คลังกลาง:
 * 1. บริจาคตรง (event_id = 999/0/NULL)     → นับเต็มทั้งหมด
 * 2. กิจกรรม Active                          → นับเต็มทั้งหมด ยังไม่หัก
 * 3. กิจกรรม Inactive (ของไม่ครบ ปิดชั่วคราว) → นับเต็มทั้งหมด ไม่หัก
 * 4. กิจกรรม Closed + ของครบเป้า            → นับเฉพาะส่วนเกิน (actual - target)
 */

// ส่วนที่ 1-3: บริจาคตรง + Active + Inactive → นับเต็ม
$res_stock = $conn->query("
    SELECT i.sub_category, SUM(d.quantity) as stock_qty
    FROM donations d
    JOIN donation_items i ON d.item_id = i.item_id
    LEFT JOIN events e ON d.event_id = e.event_id
    WHERE d.status = 'Approved'
      AND (
          d.event_id = 999
          OR d.event_id = 0
          OR d.event_id IS NULL
          OR e.status = 'Active'
          OR e.status = 'Inactive'
      )
    GROUP BY i.sub_category
");

if ($res_stock) {
    while ($s = $res_stock->fetch_assoc()) {
        $g = get_clean_group($s['sub_category']);
        if (isset($card_data[$g])) $card_data[$g]['rec'] += (float)$s['stock_qty'];
    }
}

// ส่วนที่ 4: กิจกรรม Closed + ของครบเป้า → นับเฉพาะส่วนเกิน
$res_closed_excess = $conn->query("
    SELECT i.sub_category,
           SUM(GREATEST(actual.total_rec - t.target_quantity, 0)) as stock_qty
    FROM event_item_targets t
    JOIN donation_items i ON t.item_id = i.item_id
    JOIN events e ON t.event_id = e.event_id
    JOIN (
        SELECT event_id, item_id, SUM(quantity) as total_rec
        FROM donations
        WHERE status = 'Approved'
        GROUP BY event_id, item_id
    ) actual ON actual.event_id = t.event_id AND actual.item_id = t.item_id
    WHERE e.status = 'Closed'
      AND actual.total_rec >= t.target_quantity
    GROUP BY i.sub_category
");

if ($res_closed_excess) {
    while ($s = $res_closed_excess->fetch_assoc()) {
        $g = get_clean_group($s['sub_category']);
        if (isset($card_data[$g])) $card_data[$g]['rec'] += (float)$s['stock_qty'];
    }
}

// เป้าหมายของกิจกรรมที่ Active อยู่
$res_active = $conn->query("
    SELECT i.sub_category, SUM(t.target_quantity) as total_tar 
    FROM event_item_targets t 
    JOIN donation_items i ON t.item_id = i.item_id 
    JOIN events e ON t.event_id = e.event_id 
    WHERE e.status = 'Active' 
    GROUP BY i.sub_category
");

if ($res_active) {
    while ($at = $res_active->fetch_assoc()) {
        $g = get_clean_group($at['sub_category']);
        if (isset($card_data[$g])) $card_data[$g]['tar'] = (float)$at['total_tar'];
    }
}

// --- 4. ดึงสถิติสถานะกิจกรรมทั้งหมด ---
$count_active = 0; $count_closed = 0; $count_inactive = 0;
$res_counts = $conn->query("SELECT status, COUNT(*) as c FROM events GROUP BY status");
if ($res_counts) {
    while ($row = $res_counts->fetch_assoc()) {
        if ($row['status'] === 'Active')   $count_active   = $row['c'];
        elseif ($row['status'] === 'Closed')   $count_closed   = $row['c'];
        elseif ($row['status'] === 'Inactive') $count_inactive = $row['c'];
    }
}

// --- 5. แจ้งเตือนรายการรอดำเนินการ (Pending) ---
$res_pending = $conn->query("SELECT COUNT(*) as pending_count FROM donations WHERE status = 'Pending'");
$pending_count = $res_pending ? (int)$res_pending->fetch_assoc()['pending_count'] : 0;

// --- 6. รายการกิจกรรม ---
$res_events = $conn->query("
    SELECT * FROM events 
    ORDER BY FIELD(status, 'Active', 'Inactive', 'Closed'), event_date DESC 
    LIMIT 15
");
$events_list = [];

while ($ev = $res_events->fetch_assoc()) {
    $eid      = (int)$ev['event_id']; 
    $db_status = $ev['status'];
    
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as t FROM join_event WHERE event_id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $vol_count_res = $stmt->get_result();
    $vol_count = $vol_count_res ? ($vol_count_res->fetch_assoc()['t'] ?? 0) : 0;
    
    $stmt2 = $conn->prepare("
        SELECT i.sub_category, t.target_quantity, t.item_id,
        (SELECT SUM(quantity) FROM donations 
         WHERE item_id = t.item_id AND event_id = ? AND status = 'Approved') as real_rec
        FROM event_item_targets t 
        JOIN donation_items i ON t.item_id = i.item_id 
        WHERE t.event_id = ?
    ");
    $stmt2->bind_param("ii", $eid, $eid);
    $stmt2->execute();
    $res_it = $stmt2->get_result();
    
    $items = []; 
    $is_full = true;
    $event_total_tar = 0;
    $event_total_rec = 0;
    
    if ($res_it && $res_it->num_rows > 0) {
        while ($it = $res_it->fetch_assoc()) {
            $gn         = get_clean_group($it['sub_category']); 
            $tar        = (float)$it['target_quantity']; 
            $actual_rec = (float)($it['real_rec'] ?? 0);
            $capped_rec = min($actual_rec, $tar);
            $event_total_tar += $tar;
            $event_total_rec += $capped_rec;
            $items[$gn] = [
                'item_id'    => $it['item_id'],
                'name'       => $gn,
                'tar'        => $tar,
                'capped_rec' => $capped_rec,
                'actual_rec' => $actual_rec,
                'color'      => getColor($gn),
            ];
            if ($actual_rec < $tar) { $is_full = false; }
        }
    } else {
        $is_full = false;
    }

    $overall_pct = ($event_total_tar > 0) ? ($event_total_rec / $event_total_tar) * 100 : 0;
    $days_left   = ceil((strtotime($ev['event_date']) - time()) / 86400);

    $events_list[] = [
        'info'            => $ev, 
        'items'           => $items, 
        'v_count'         => $vol_count, 
        'is_full_logic'   => $is_full,
        'display_mode'    => strtolower($db_status),
        'event_total_tar' => $event_total_tar,
        'event_total_rec' => $event_total_rec,
        'overall_pct'     => $overall_pct,
        'days_left'       => $days_left,
    ];
}

// --- คำนวณ globalMax หลัง events_list พร้อมแล้ว ---
$global_max_val = 0;
foreach ($cats_list as $c) {
    $global_max_val = max($global_max_val, $card_data[$c]['rec'], $card_data[$c]['tar']);
}
foreach ($events_list as $e) {
    foreach ($e['items'] as $it) {
        $global_max_val = max($global_max_val, $it['tar'], $it['actual_rec'], $it['capped_rec']);
    }
}
$globalMax      = (int)ceil($global_max_val * 1.1);
$globalStepSize = (int)ceil($globalMax / 5);
if ($globalMax <= 0)      { $globalMax = 100; }
if ($globalStepSize <= 0) { $globalStepSize = 20; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - จิตอาสาและบริจาค</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'IBM Plex Sans Thai', sans-serif; }
        .main-wrapper { margin-left: 280px; padding: 40px; transition: all 0.3s; }
        @media (max-width: 991px) { .main-wrapper { margin-left: 0; } }
        .member-status { background: #6366f1; color: white; padding: 12px 20px; border-radius: 18px; box-shadow: 0 8px 15px rgba(99, 102, 241, 0.2); min-width: 120px; }
        .member-status.total { background: #10b981; }
        .stat-card { border: none; border-radius: 18px; color: white; padding: 22px; height: 100%; position: relative; overflow: hidden; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card i { position: absolute; right: 8px; bottom: 8px; font-size: 3.8rem; opacity: 0.12; }
        .stat-val { font-size: 2.3rem; font-weight: 700; margin: 8px 0; }
        .content-card { background: white; border-radius: 25px; padding: 30px; margin-bottom: 30px; border: 1px solid #e1e4e8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .closed-event-style { opacity: 0.8; background: #f8fafc; filter: grayscale(0.2); }
        .d-none-custom { display: none !important; }
        .btn-close-action { background: #fee2e2; color: #ef4444; padding: 8px 20px; border-radius: 12px; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; }
        .btn-close-action:hover { background: #fecaca; }
        .btn-open-action { background: #dcfce7; color: #16a34a; padding: 8px 20px; border-radius: 12px; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; }
        .btn-open-action:hover { background: #bbf7d0; }
        .v-badge { background: #e0f2fe; color: #0369a1; padding: 6px 15px; border-radius: 12px; font-weight: 700; }
        .badge-ended { background-color: #f0fdf4 !important; color: #16a34a !important; border: 1px solid #bbf7d0 !important; font-size: 0.9rem; font-weight: 600; padding: 10px 20px !important; border-radius: 50px !important; display: inline-flex; align-items: center; }
        .event-header-flex { display: flex; align-items: center; gap: 24px; margin-bottom: 24px; flex-wrap: wrap; }
        .date-block { background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 18px 24px; border-radius: 20px; text-align: center; min-width: 100px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2); transition: 0.3s; }
        .date-block:hover { transform: translateY(-3px); box-shadow: 0 15px 20px -3px rgba(99, 102, 241, 0.3); }
        .date-month { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; opacity: 0.85; line-height: 1; margin-bottom: 4px; }
        .date-day { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .date-year { font-size: 0.8rem; font-weight: 600; opacity: 0.7; margin-top: 4px; }
        .meta-group { display: flex; gap: 32px; flex-wrap: wrap; flex-grow: 1; }
        .meta-stat { display: flex; flex-direction: column; gap: 4px; }
        .meta-stat-label { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 6px; }
        .meta-stat-value { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
        .meta-stat-value.urgent { color: #ef4444; }
        .event-title-row { flex-grow: 1; min-width: 300px; }
        .event-title-h3 { font-size: 1.6rem; font-weight: 800; color: #1e293b; margin-bottom: 8px !important; }
        .item-progress-card { background: white; border: 1px solid #f1f5f9; border-radius: 18px; padding: 18px; transition: transform 0.2s, box-shadow 0.2s; }
        .item-progress-card:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -3px rgba(0,0,0,0.06); border-color: #e2e8f0; }
        .btn-show-all { display: none; width: fit-content; margin: 30px auto; padding: 12px 35px; border-radius: 15px; background: #ffffff; border: 1.5px solid #e2e8f0; font-weight: 700; color: #64748b; transition: 0.3s; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn-show-all:hover { background: #f8fafc; color: #1e293b; border-color: #cbd5e1; transform: translateY(-2px); }
        .btn-show-all i { margin-right: 8px; transition: 0.3s; }
        .btn-show-all.expanded i { transform: rotate(180deg); }
        .d-none-limit { display: none !important; }
        .summary-table-container { border: 1px solid #e2e8f0; border-radius: 20px; overflow-y: auto; background: white; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); max-height: 250px; }
        .summary-table-container::-webkit-scrollbar { width: 6px; }
        .summary-table-container::-webkit-scrollbar-track { background: #f1f5f9; }
        .summary-table-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .summary-table-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .summary-table { width: 100%; margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
        .summary-table th { background: #f8fafc; padding: 16px 24px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.9rem; }
        .summary-table td { padding: 14px 24px; color: #475569; border-bottom: 1px solid #f1f5f9; font-weight: 600; font-size: 0.9rem; vertical-align: middle; }
        .summary-table tr:last-child td { border-bottom: none; }
        .summary-table tr:hover td { background-color: #f8fafc; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-dot.active { background-color: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }
        .status-dot.inactive { background-color: #f59e0b; box-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }
        .status-dot.closed { background-color: #ef4444; box-shadow: 0 0 8px rgba(239, 68, 68, 0.4); }
        .btn-view-detail { padding: 6px 18px; border-radius: 20px; border: 1.5px solid #6366f1; background: transparent; color: #6366f1; font-weight: 700; font-size: 0.8rem; transition: 0.2s; cursor: pointer; }
        .btn-view-detail:hover { background: #6366f1; color: white; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); }
        .summary-row.d-none-custom { display: none !important; }
        .btn-toggle { display: none; margin: 5px auto; padding: 8px 28px; border-radius: 50px; background: #ffffff; border: 1.5px solid #e2e8f0; font-weight: 700; color: #6366f1; font-size: 0.85rem; transition: 0.3s; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn-toggle:hover { background: #6366f1; color: white; border-color: #6366f1; transform: translateY(-1px); box-shadow: 0 10px 15px -1px rgba(99, 102, 241, 0.2); }
        .btn-toggle-icon { transition: 0.3s; }
        .btn-toggle.expanded .btn-toggle-icon { transform: rotate(180deg); }
    </style>
</head>
<body>

<?php include('menu_admin.php'); ?>

<div class="main-wrapper">
    
    <?php if ($pending_count > 0): ?>
    <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center justify-content-between mb-4 mt-2" style="border-radius: 18px; background: linear-gradient(145deg, #fffbeb, #fef3c7);">
        <div>
            <span class="fs-3 me-3 text-warning"><i class="fas fa-bell fa-shake"></i></span>
            <strong class="fs-5 text-dark">แจ้งเตือนด่วน!</strong> 
            <span class="text-muted ms-2 mt-1 d-inline-block">ขณะนี้มีรายการบริจาคที่รอการตรวจสอบและอนุมัติ <b><?= number_format($pending_count) ?></b> รายการ</span>
        </div>
        <a href="admin_history.php" class="btn fw-bold px-4 py-2" style="border-radius: 12px; background: #f59e0b; color: white; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);"><i class="fas fa-tasks me-2"></i>ตรวจสอบและอนุมัติ</a>
    </div>
    <?php endif; ?>

    <div class="header-box d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold m-0">Dashboard <span style="color:#10b981">Admin</span></h1>
            <p class="text-muted">จัดการและวิเคราะห์กิจกรรมทั้งหมด</p>
        </div>
        <div class="d-flex gap-2">
            <div class="member-status text-center" style="background: #16a34a;"><small class="fw-bold opacity-75">กิจกรรมที่เปิดอยู่</small><div class="h4 fw-bold m-0"><?= number_format($count_active) ?></div></div>
            <div class="member-status text-center" style="background: #f59e0b;"><small class="fw-bold opacity-75">กิจกรรมที่ไม่ครบ</small><div class="h4 fw-bold m-0"><?= number_format($count_inactive) ?></div></div>
            <div class="member-status text-center" style="background: #ef4444;"><small class="fw-bold opacity-75">กิจกรรมที่สิ้นสุดแล้ว</small><div class="h4 fw-bold m-0"><?= number_format($count_closed) ?></div></div>
            <div class="member-status text-center"><small class="fw-bold opacity-75">อาสาสมัคร</small><div class="h4 fw-bold m-0"> <?= number_format($total_volunteers) ?></div></div>
            <div class="member-status total text-center"><small class="fw-bold opacity-75">สมาชิก</small><div class="h4 fw-bold m-0"><?= number_format($total_members) ?></div></div>
        </div>
    </div>

    <div class="row g-4 mb-5">
    <?php foreach ($cats_list as $name): 
        $d = $card_data[$name] ?? ['tar' => 0, 'rec' => 0];
        $c = getColor($name);
    ?>
    <div class="col-md-3">
        <div class="stat-card" style="background-color: <?= $c ?>; min-height: 160px;">
            <i class="fas fa-box"></i>
            <div class="small fw-bold opacity-90 mb-1"><?= $name ?></div>
            <div class="stat-val d-flex align-items-baseline">
                <span class="h2 fw-bold m-0"><?= number_format($d['rec'], 0) ?></span>
                <span class="ms-2 opacity-75" style="font-size: 0.9rem;">กิโลกรัม</span>
            </div>
        </div>
    </div> 
    <?php endforeach; ?>
    </div>

    <div class="content-card mb-5">
        <h5 class="fw-bold mb-4 text-primary"><i class="fas fa-chart-line me-2"></i>ภาพรวมคลังสิ่งของแยกตามหมวดหมู่</h5>
        <div style="height: 350px;"><canvas id="mainTotalChart"></canvas></div>
    </div>

    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="fw-bold m-0"><i class="fas fa-list-ul me-2 text-primary"></i>รายละเอียดกิจกรรม</h4>
            <div class="d-flex gap-2">
                <select class="form-select" id="statusFilter" style="border-radius: 12px; width: 230px;" onchange="unifiedFilter()">
                    <option value="active" selected>แสดงกิจกรรมที่เปิดรับอยู่</option>
                    <option value="inactive">แสดงกิจกรรมที่ปิดชั่วคราว</option>
                    <option value="closed">แสดงกิจกรรมที่ปิดถาวร</option>
                </select>
                <input type="text" id="filterInputName" class="form-control" style="width: 200px; border-radius: 12px;" placeholder="ค้นชื่อ..." oninput="unifiedFilter()">
            </div>
        </div>

        <div class="summary-table-container">
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>ชื่อกิจกรรม</th>
                        <th>วันที่จัด</th>
                        <th>สถานะปัจจุบัน</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody id="summaryTableBody">
                <?php foreach ($events_list as $e): 
                    $eid     = $e['info']['event_id'];
                    $ename   = $e['info']['event_name'];
                    $edate   = date('d/m/Y', strtotime($e['info']['event_date'] ?? 'now'));
                    $estatus = strtolower($e['info']['status']);
                    $status_text = ($estatus == 'active') ? 'เปิดอยู่' : (($estatus == 'inactive') ? 'ปิดชั่วคราว' : 'ปิดถาวร');
                ?>
                    <tr class="summary-row" data-mode="<?= strtolower($estatus) ?>" data-name="<?= strtolower($ename) ?>">
                        <td><?= htmlspecialchars($ename) ?></td>
                        <td><?= $edate ?></td>
                        <td>
                            <span class="status-dot <?= strtolower($estatus) ?>"></span>
                            <?= $status_text ?>
                        </td>
                        <td>
                            <button class="btn-view-detail" onclick="scrollToEvent('event_card_<?= $eid ?>')">ดูรายละเอียด</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="text-align:center; margin-top:10px; margin-bottom: 20px;">
                <button id="toggleTableBtn" onclick="toggleTable()" class="btn-toggle">
                    <i class="fas fa-chevron-down me-2 btn-toggle-icon"></i> <span class="txt">ดูทั้งหมด</span>
                </button>
            </div>
        </div>

        <div id="noEventMsg" style="display:none;" class="text-center py-5 my-4 text-muted border border-2 border-dashed rounded-4 bg-light">
            <i class="fas fa-search mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
            <h5 class="fw-bold">ไม่พบกิจกรรมที่ค้นหา</h5>
            <p class="mb-0">ลองเปลี่ยนสถานะหรือคำค้นหาด้านบนดูอีกครั้ง</p>
        </div>

        <div id="eventsContainer">
        <?php if (empty($events_list)): ?>
            <div class="text-center py-5 my-4 border border-2 border-dashed rounded-4 bg-light">
                <i class="fas fa-calendar-times mb-3" style="font-size: 4rem; color: #cbd5e1;"></i>
                <h4 class="fw-bold text-secondary">ยังไม่มีข้อมูลกิจกรรม</h4>
                <p class="text-muted mb-4">เริ่มต้นสร้างกิจกรรมอาสาแรกของคุณเพื่อเปิดรับบริจาค</p>
                <a href="addevent.php" class="btn btn-primary px-4 shadow-sm" style="border-radius: 12px;"><i class="fas fa-plus me-2"></i>สร้างกิจกรรมใหม่</a>
            </div>
        <?php else: ?>
        <?php foreach ($events_list as $e): 
            $eid = $e['info']['event_id']; 
            $current_ev_status = $e['info']['status'];
        ?>
            <div class="event-item mb-4 <?= $e['display_mode'] !== 'active' ? 'd-none-custom' : '' ?>" 
                 id="event_card_<?= $eid ?>" 
                 data-mode="<?= $e['display_mode'] ?>" 
                 data-name="<?= htmlspecialchars(strtolower($e['info']['event_name']), ENT_QUOTES, 'UTF-8') ?>">

                <div class="content-card <?= $current_ev_status !== 'Active' ? 'closed-event-style' : '' ?>">
                    <div class="event-header-flex">
                        <div class="date-block">
                            <div class="date-month"><?= date('M', strtotime($e['info']['event_date'])) ?></div>
                            <div class="date-day"><?= date('d', strtotime($e['info']['event_date'])) ?></div>
                            <div class="date-year"><?= date('Y', strtotime($e['info']['event_date'])) ?></div>
                        </div>

                        <div class="event-title-row">
                            <h3 class="event-title-h3 m-0">
                                <?= htmlspecialchars($e['info']['event_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($current_ev_status === 'Closed'): ?>
                                    <span class="badge bg-success ms-2" style="font-size: 0.35em; vertical-align: middle; padding: 8px 15px; border-radius: 50px;"><i class="fas fa-check-circle me-1"></i> ของครบ</span>
                                <?php elseif ($current_ev_status === 'Inactive'): ?>
                                    <span class="badge bg-warning text-dark ms-2" style="font-size: 0.35em; vertical-align: middle; padding: 8px 15px; border-radius: 50px;"><i class="fas fa-archive me-1"></i> ค้างในคลัง</span>
                                <?php endif; ?>
                            </h3>
                            
                            <div class="meta-group mt-3">
                                <div class="meta-stat">
                                    <div class="meta-stat-label"><i class="fas fa-map-marker-alt" style="color:#6366f1"></i> สถานที่จัดงาน</div>
                                    <div class="meta-stat-value"><?= htmlspecialchars($e['info']['Location'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="meta-stat">
                                    <div class="meta-stat-label"><i class="fas fa-user-friends" style="color:#10b981"></i> จำนวนจิตอาสา</div>
                                    <div class="meta-stat-value"><?= number_format($e['v_count']) ?> คน</div>
                                    <div class="badge bg-light text-dark border"><i class="fas fa-history me-1"></i> เปิดกิจกรรมรอบที่ <?= number_format($e['info']['closed_count'] + 1) ?> </div>
                                </div>
                                <?php if ($e['display_mode'] === 'active'): ?>
                                    <div class="meta-stat">
                                        <div class="meta-stat-label"><i class="fas fa-stopwatch" style="color:<?= ($e['days_left'] <= 3) ? '#ef4444' : '#f59e0b' ?>"></i>ระยะเวลาที่เหลือ</div>
                                        <div class="meta-stat-value <?= ($e['days_left'] <= 3) ? 'urgent' : 'text-success' ?>">
                                            <?= $e['days_left'] > 0 ? "อีก " . $e['days_left'] . " วัน" : "วันนี้แล้ว!" ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="event-actions ms-auto">
                            <?php if ($current_ev_status === 'Active'): ?>
                                <button class="btn-close-action shadow-sm" style="padding: 12px 25px; border-radius: 15px;" onclick="updateStatus(<?= $eid ?>, 'Closed', <?= $e['is_full_logic'] ? 'true' : 'false' ?>)">ปิดกิจกรรม & หักของ</button>
                            <?php elseif ($e['is_full_logic'] === false): ?>
                                <button class="btn-open-action shadow-sm" style="padding: 12px 25px; border-radius: 15px;" onclick="updateStatus(<?= $eid ?>, 'Active', false)">เปิดรับบริจาคใหม่</button>
                            <?php else: ?>
                                <span class="badge-ended">กิจกรรมสิ้นสุดแล้ว</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 mb-4" style="width: 100%; max-width: 700px;">
                        <div class="d-flex justify-content-between small fw-bold mb-2 text-secondary">
                            <span><i class="fas fa-bullseye me-1 text-primary"></i> ภาพรวมความคืบหน้าจำนวนของที่ได้รับ</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1" style="border-radius: 50px;"><?= number_format(min($e['overall_pct'], 100), 1) ?>%</span>
                        </div>
                        <div class="progress shadow-sm" style="height: 12px; border-radius: 10px; background-color: #f1f5f9; border: 1px solid #e2e8f0;">
                            <div class="progress-bar <?= ($e['overall_pct'] >= 100) ? 'bg-success' : 'progress-bar-striped progress-bar-animated' ?>" style="width:<?= min($e['overall_pct'], 100) ?>%; background-color: #6366f1;"></div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-center mb-3 <?= $current_ev_status === 'Closed' ? 'text-warning' : 'text-secondary' ?>" style="font-size: 0.95rem;">
                                <?= $current_ev_status === 'Closed' ? 'ยอดบริจาคที่ได้รับจริง' : 'เป้าหมายการบริจาค' ?>
                            </h6>
                            <div style="height: 250px;"><canvas id="chart_capped_<?= $eid ?>"></canvas></div>
                        </div>
                        <?php if ($current_ev_status !== 'Closed'): ?>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-center mb-3 text-warning" style="font-size: 0.95rem;">ยอดบริจาคที่ได้รับจริง</h6>
                            <div style="height: 250px;"><canvas id="chart_actual_<?= $eid ?>"></canvas></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-light p-4 rounded-5 border-0 shadow-inner" style="background-color: #f8fafc !important;">
                        <p class="fw-bold border-bottom pb-2 mb-4 text-secondary d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-boxes me-2"></i>ความคืบหน้าของบริจาคและการเติมคลัง</span>
                            <small class="fw-normal opacity-75">แยกตามหมวดหมู่</small>
                        </p>
                        <div class="row g-3">
                            <?php foreach ($e['items'] as $it): $pct = ($it['tar'] > 0) ? ($it['actual_rec'] / $it['tar']) * 100 : 0; ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="item-progress-card">
                                    <div class="d-flex justify-content-between small fw-bold mb-2">
                                        <span class="text-dark"><?= $it['name'] ?></span>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="<?= ($it['actual_rec'] >= $it['tar']) ? 'text-success' : 'text-primary' ?>"><?= number_format($it['actual_rec'], 0) ?></span>
                                            <span class="text-muted fw-normal">/ <?= number_format($it['tar'], 0) ?></span>
                                        </div>
                                    </div>
                                    <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                        <div class="progress-bar <?= ($it['actual_rec'] >= $it['tar']) ? 'bg-success' : '' ?> shadow-sm" style="width:<?= min($pct, 100) ?>%; background-color:<?= ($it['actual_rec'] >= $it['tar']) ? '' : $it['color'] ?>;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted fw-bold"><?= number_format(min($pct, 100), 0) ?>%</small>
                                        <?php if ($current_ev_status === 'Active' && $it['actual_rec'] < $it['tar']): ?>
                                            <button class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" style="font-size: 0.75rem; color: #6366f1;" onclick="transferStock(<?= $it['item_id'] ?>, <?= $eid ?>, '<?= $it['name'] ?>', <?= ($it['tar'] - $it['actual_rec']) ?>)">
                                                <i class="fas fa-plus-circle me-1"></i>เติมคลัง
                                            </button>
                                        <?php elseif ($it['actual_rec'] >= $it['tar']): ?>
                                            <span class="text-success" style="font-size: 0.7rem;"><i class="fas fa-check-circle"></i> เป้าหมายครบ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
        
        <button id="btnShowAll_Master" class="btn-show-all" onclick="toggleShowAllMaster()">
            <i class="fas fa-chevron-down"></i> <span class="txt">แสดงกิจกรรมทั้งหมด</span>
        </button>
    </div>
</div>

<script>
Chart.defaults.font.family = "'IBM Plex Sans Thai'";

// globalMax ใช้ร่วมกันทุกกราฟ
const GLOBAL_MAX       = <?= $globalMax ?>;
const GLOBAL_STEP_SIZE = <?= $globalStepSize ?>;

// --- กราฟรวม (ใช้ globalMax เดียวกัน) ---
new Chart(document.getElementById('mainTotalChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($cats_list) ?>,
        datasets: [
            {
                label: 'เป้าหมายกิจกรรม',
                data: <?= json_encode(array_map(function($c) use ($card_data){ return $card_data[$c]['tar']; }, $cats_list)) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'ที่มีอยู่ในคลัง',
                data: <?= json_encode(array_map(function($c) use ($card_data){ return $card_data[$c]['rec']; }, $cats_list)) ?>,
                backgroundColor: '#10b981',
                type: 'bar',
                borderRadius: 5,
                maxBarThickness: 45
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: GLOBAL_MAX,
                ticks: { stepSize: GLOBAL_STEP_SIZE }
            }
        }
    }
});

// --- กราฟแต่ละกิจกรรม (ใช้ globalMax เดียวกัน) ---
<?php
$cats_list_js = json_encode($cats_list);
foreach ($events_list as $e):
    $eid      = $e['info']['event_id'];
    $isClosed = ($e['info']['status'] === 'Closed');

    // สร้าง data เรียงตาม $cats_list
    $tar_ordered    = [];
    $capped_ordered = [];
    $actual_ordered = [];
    $stock_ordered  = [];

    foreach ($cats_list as $cname) {
        $tar_ordered[]    = $e['items'][$cname]['tar']        ?? 0;
        $capped_ordered[] = $e['items'][$cname]['capped_rec'] ?? 0;
        $actual_ordered[] = $e['items'][$cname]['actual_rec'] ?? 0;
        $stock_ordered[]  = $card_data[$cname]['rec']         ?? 0;
    }
?>

<?php if ($isClosed): ?>
new Chart(document.getElementById('chart_capped_<?= $eid ?>'), {
    type: 'line',
    data: {
        labels: <?= $cats_list_js ?>,
        datasets: [
            {
                label: 'เป้าหมาย',
                data: <?= json_encode($tar_ordered) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                borderWidth: 3,
                type: 'line',
                order: 1
            },
            {
                label: 'ยอดที่ได้รับจริง',
                data: <?= json_encode($actual_ordered) ?>,
                backgroundColor: '#f59e0b',
                type: 'bar',
                borderRadius: 6,
                maxBarThickness: 45,
                order: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: GLOBAL_MAX, ticks: { stepSize: GLOBAL_STEP_SIZE } } }
    }
});
<?php else: ?>
new Chart(document.getElementById('chart_capped_<?= $eid ?>'), {
    type: 'line',
    data: {
        labels: <?= $cats_list_js ?>,
        datasets: [
            {
                label: 'เป้าหมาย',
                data: <?= json_encode($tar_ordered) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                borderWidth: 3,
                type: 'line',
                order: 1
            },
            {
                label: 'ที่มีอยู่ในคลัง',
                data: <?= json_encode($stock_ordered) ?>,
                backgroundColor: '#10b981',
                type: 'bar',
                borderRadius: 5,
                maxBarThickness: 45,
                order: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: GLOBAL_MAX, ticks: { stepSize: GLOBAL_STEP_SIZE } } }
    }
});
<?php endif; ?>

<?php if (!$isClosed): ?>
new Chart(document.getElementById('chart_actual_<?= $eid ?>'), {
    type: 'line',
    data: {
        labels: <?= $cats_list_js ?>,
        datasets: [
            {
                label: 'เป้าหมาย',
                data: <?= json_encode($tar_ordered) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                order: 1,
                type: 'line'
            },
            {
                label: 'ยอดที่ได้รับจริง (รวมส่วนเกิน)',
                data: <?= json_encode($actual_ordered) ?>,
                backgroundColor: '#f59e0b',
                type: 'bar',
                borderRadius: 6,
                maxBarThickness: 45,
                order: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: GLOBAL_MAX, ticks: { stepSize: GLOBAL_STEP_SIZE } } }
    }
});
<?php endif; ?>
<?php endforeach; ?>

// --- Filter Logic ---
let showAllTable = false;
let showAllMaster = false;

function toggleTable() {
    showAllTable = !showAllTable;
    unifiedFilter();
}

function toggleShowAllMaster() {
    showAllMaster = !showAllMaster;
    unifiedFilter();
}

function scrollToEvent(id) {
    if (!showAllMaster) { showAllMaster = true; }
    unifiedFilter();
    setTimeout(() => {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.style.boxShadow = "0 0 0 5px rgba(99, 102, 241, 0.3)";
            setTimeout(() => { el.style.boxShadow = ""; }, 2000);
        }
    }, 100);
}

function unifiedFilter() {
    const modeVal = document.getElementById('statusFilter').value;
    const nameVal = document.getElementById('filterInputName').value.toLowerCase();
    let mCountSum = 0;
    let mCountCard = 0;

    document.querySelectorAll('.summary-row').forEach(row => {
        const isMatch = (modeVal === 'all' || modeVal === row.getAttribute('data-mode'))
                     && row.getAttribute('data-name').includes(nameVal);
        row.style.display = 'none';
        if (isMatch) {
            mCountSum++;
            if (showAllTable || mCountSum <= 3) row.style.display = 'table-row';
        }
    });

    const tBtn = document.getElementById('toggleTableBtn');
    if (tBtn) {
        tBtn.style.display = (mCountSum > 3) ? 'inline-block' : 'none';
        tBtn.classList.toggle('expanded', showAllTable);
        tBtn.querySelector('.txt').innerText = showAllTable ? 'ย่อกลับ' : 'ดูทั้งหมด';
    }

    document.querySelectorAll('.event-item').forEach(el => {
        const isMatch = (modeVal === 'all' || modeVal === el.getAttribute('data-mode'))
                     && el.getAttribute('data-name').includes(nameVal);
        el.classList.add('d-none-custom');
        el.classList.remove('d-none-limit');
        if (isMatch) {
            mCountCard++;
            if (!showAllMaster && mCountCard > 3) {
                el.classList.add('d-none-limit');
            } else {
                el.classList.remove('d-none-custom');
            }
        }
    });

    const mBtn = document.getElementById('btnShowAll_Master');
    if (mBtn) {
        mBtn.style.display = (mCountCard > 3) ? 'block' : 'none';
        mBtn.classList.toggle('expanded', showAllMaster);
        mBtn.querySelector('.txt').innerText = showAllMaster ? 'แสดงน้อยลง' : 'แสดงกิจกรรมทั้งหมด';
    }

    const noMsg = document.getElementById('noEventMsg');
    if (noMsg) noMsg.style.display = (Math.max(mCountSum, mCountCard) === 0) ? 'block' : 'none';

    const tableContainer = document.querySelector('.summary-table-container');
    if (tableContainer) tableContainer.style.display = (mCountSum === 0) ? 'none' : 'block';
}

document.addEventListener('DOMContentLoaded', () => { unifiedFilter(); });

// --- Fetch helper ---
function sendFetch(url, data) {
    Swal.fire({ title: 'กำลังประมวลผล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    let fd = new FormData();
    for (let key in data) { fd.append(key, data[key]); }
    fetch(url, { method: 'POST', body: fd })
    .then(res => res.text())
    .then(rawText => {
        try {
            const resData = JSON.parse(rawText.substring(rawText.indexOf('{')));
            if (resData.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ!', timer: 800, showConfirmButton: false })
                    .then(() => { window.location.reload(); });
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: resData.message });
            }
        } catch (e) { window.location.reload(); }
    });
}

// --- เติมของจากคลัง ---
function transferStock(itemId, eventId, itemName, neededQty) {
    Swal.fire({
        title: '📦 เติมของจากคลัง',
        html: `<small>เติม <b>${itemName}</b> (ขาดอีก: ${neededQty} กก.)</small>`,
        input: 'number',
        inputAttributes: { min: 1, step: 1 },
        inputValue: Math.ceil(neededQty),
        showCancelButton: true,
        confirmButtonText: 'ยืนยันการเติม',
        confirmButtonColor: '#6366f1'
    }).then((result) => {
        if (result.isConfirmed && result.value > 0) {
            sendFetch('transfer_stock.php', { item_id: itemId, event_id: eventId, amount: result.value });
        }
    });
}

// --- ปิด / เปิดกิจกรรม ---
function updateStatus(id, newStatus, isFull) {
    const notFull = (isFull === false || isFull === 'false' || isFull === 0 || isFull === '0');
    if (newStatus === 'Closed' && notFull) {
        Swal.fire({
            title: 'ของยังไม่ครบเป้าหมาย!',
            text: "หากปิดตอนนี้ ระบบจะเปลี่ยนสถานะเป็น 'ค้างในคลัง' (Inactive) และจะยังไม่หักของออกจากคลังหลัก ยืนยันหรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน (ไม่หักของ)',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#f59e0b'
        }).then((result) => {
            if (result.isConfirmed) {
                sendFetch('update_event_status.php', { event_id: id, status: 'Inactive' });
            }
        });
        return;
    }
    sendFetch('update_event_status.php', { event_id: id, status: newStatus });
}
</script>

</body>
</html>