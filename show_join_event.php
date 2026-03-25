<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

// 1. ตรวจสอบสิทธิ์ (Security Check)
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// 2. เชื่อมต่อฐานข้อมูล
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "project");
}
if ($conn->connect_error) die("Database Connection Failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// --- ส่วนที่ 1: ระบบค้นหา ---
$search = isset($_GET['search_name']) ? $conn->real_escape_string($_GET['search_name']) : '';
$where_search = "";
if ($search != '') {
    $where_search = " AND (u.first_name LIKE '%$search%' OR e.event_name LIKE '%$search%') ";
}

// --- ส่วนที่ 2: สถิติรวม (Stats) ---
$total_events = $conn->query("SELECT COUNT(*) FROM events")->fetch_row()[0];
$total_volunteers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'volunteer' AND status = 'active'")->fetch_row()[0] ?? 0;
$total_confirmed = $conn->query("SELECT COUNT(*) FROM join_event WHERE status = 1")->fetch_row()[0] ?? 0;
$total_cancelled = $conn->query("SELECT COUNT(*) FROM join_event WHERE status = 0")->fetch_row()[0] ?? 0;

$sql_pending_total = "
    SELECT (COUNT(u.id) * (SELECT COUNT(*) FROM events)) - 
           (SELECT COUNT(*) FROM join_event j 
            INNER JOIN users u2 ON j.user_id = u2.id 
            WHERE u2.role = 'volunteer' AND u2.status = 'active')
    FROM users u 
    WHERE u.role = 'volunteer' AND u.status = 'active'";
$total_pending = $conn->query($sql_pending_total)->fetch_row()[0] ?? 0;
if($total_pending < 0) $total_pending = 0;

// --- ส่วนที่ 3: สรุปรายกิจกรรม (ตารางที่ 1) ---
$sql_summary = "
    SELECT e.event_id, e.event_name, e.event_date,
        SUM(CASE WHEN j.status = 1 THEN 1 ELSE 0 END) as confirmed_count,
        SUM(CASE WHEN j.status = 0 THEN 1 ELSE 0 END) as cancelled_count,
        (SELECT COUNT(*) FROM users WHERE role = 'volunteer' AND status = 'active') - 
        COUNT(j.user_id) as pending_count
    FROM events e
    LEFT JOIN join_event j ON e.event_id = j.event_id AND j.user_id IN (SELECT id FROM users WHERE status = 'active')
    WHERE e.event_name LIKE '%$search%'
    GROUP BY e.event_id
    ORDER BY e.event_date DESC";
$res_summary = $conn->query($sql_summary);

// --- ส่วนที่ 4: รายชื่ออาสาสมัครและกิจกรรม ---
$sql_list = "
    SELECT 
        u.first_name, u.last_name, u.id as user_pk_id, u.profile_image_path,
        e.event_id, e.event_name, e.event_date, 
        j.status as reg_status
    FROM users u
    CROSS JOIN events e
    LEFT JOIN join_event j ON u.id = j.user_id AND e.event_id = j.event_id
    WHERE u.role = 'volunteer' AND u.status = 'active'
    $where_search
    ORDER BY e.event_date DESC, u.first_name ASC";
$result_list = $conn->query($sql_list);

// ดึงรายชื่ออาสาสมัครแบบไม่ซ้ำ (Unique Users) สำหรับโชว์ทำเนียบด้านบน
$sql_unique_v = "SELECT id, first_name, last_name, profile_image_path FROM users WHERE role = 'volunteer' AND status = 'active' ORDER BY first_name ASC";
$res_unique_v = $conn->query($sql_unique_v);

// --- ฟังก์ชันเสริม ---
function thai_date_short($date) {
    if(!$date || $date == '0000-00-00') return "-";
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $d = date("j", strtotime($date));
    $m = $months[date("n", strtotime($date))];
    $y = date("Y", strtotime($date)) + 543;
    return "$d $m $y";
}
?>

<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการเข้าร่วมกิจกรรม | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'IBM Plex Sans Thai', sans-serif; background: #f8fafc; margin: 0; }
        .admin-layout { margin-left: 280px; padding: 20px; transition: 0.3s ease; }
        .stat-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #eef2f6; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; }
        .c-blue::before { background: #3b82f6; } .c-purple::before { background: #6366f1; }
        .c-orange::before { background: #f59e0b; } .c-green::before { background: #10b981; }
        .c-red::before { background: #ef4444; }
        .circle-num { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-block; min-width: 90px; text-align: center; }
        @media (max-width: 1024px) { .admin-layout { margin-left: 0; } }
        .event-row.hidden-event { display: none; }
    </style>
</head>
<body>
    <?php include('menu_admin.php'); ?>

    <div class="admin-layout"> 
        <div class="p-4 md:p-10">
            <div class="max-w-7xl mx-auto">
                
                <div class="mb-8 flex justify-between items-end">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800 tracking-tight">รายงาน <span class="text-emerald-500">การเข้าร่วมกิจกรรม</span></h2>
                        <p class="text-slate-400 text-sm mt-1">อัปเดตข้อมูลล่าสุด: <?php echo date('H:i'); ?> น. (เฉพาะอาสาสมัครที่ยืนยันตัวตนแล้ว)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-10">
                    <div class="stat-card c-blue">
                        <p class="text-slate-400 text-[11px] font-bold uppercase mb-1">กิจกรรมทั้งหมด</p>
                        <div class="text-2xl font-black text-blue-600"><?= $total_events ?></div>
                    </div>
                    <div class="stat-card c-purple">
                        <p class="text-slate-400 text-[11px] font-bold uppercase mb-1">อาสาสมัคร</p>
                        <div class="text-2xl font-black text-indigo-600"><?= $total_volunteers ?></div>
                    </div>
                    <div class="stat-card c-orange">
                        <p class="text-orange-500 text-[11px] font-bold uppercase mb-1">ยังไม่ได้เข้าร่วม</p>
                        <div class="text-2xl font-black text-orange-500"><?= $total_pending ?></div>
                    </div>
                    <div class="stat-card c-green">
                        <p class="text-emerald-600 text-[11px] font-bold uppercase mb-1">เข้าร่วมแล้ว</p>
                        <div class="text-2xl font-black text-emerald-600"><?= $total_confirmed ?></div>
                    </div>
                    <div class="stat-card c-red">
                        <p class="text-rose-500 text-[11px] font-bold uppercase mb-1">ยกเลิกแล้ว</p>
                        <div class="text-2xl font-black text-rose-600"><?= $total_cancelled ?></div>
                    </div>
                </div>

         <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

   <!-- List -->
<div class="divide-y divide-gray-100" id="volunteer-list">
    <?php 
    $index = 0;
    $res_unique_v->data_seek(0); 
    while($uv = $res_unique_v->fetch_assoc()): 
        $index++;
        $hidden_class = ($index > 3) ? 'hidden extra-item' : ''; // ✅ FIX 5 → 3
    ?>

    <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition <?= $hidden_class ?>">

        <!-- รูป -->
        <img 
            src="<?= $uv['profile_image_path'] ?: 'uploads/profiles/default.png' ?>" 
            class="w-12 h-12 rounded-full object-cover border"
        >

        <!-- ข้อมูล -->
        <div class="flex-1">
            <div class="text-sm font-semibold text-gray-800">
                <?= $uv['first_name'].' '.$uv['last_name'] ?>
            </div>
            <div class="text-xs text-gray-400">
                ID: #<?= $uv['id'] ?>
            </div>
        </div>

        <!-- สถานะ -->
        <div class="text-xs font-semibold text-green-600 bg-green-50 px-3 py-1 rounded-full">
            ยืนยันตัวตนแล้ว
        </div>

    </div>

    <?php endwhile; ?>
</div>

<!-- ปุ่ม -->
<?php if($index > 3): // ✅ FIX 5 → 3 ?>
<div class="px-6 py-3 border-t text-center bg-gray-50">
    <button onclick="toggleVolunteerList(this)" 
        class="text-sm font-semibold text-blue-600 hover:underline">
        แสดงทั้งหมด
    </button>
</div>
<?php endif; ?>

</div>
               <section class="bg-white rounded-2xl border border-gray-200 shadow-sm mb-10 overflow-hidden">

    
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm mb-10 overflow-x-auto">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-lg font-bold text-slate-700">สถานะรายกิจกรรม</h3>
                        <form method="GET" class="flex gap-2">
                            <input type="text" name="search_name" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาชื่อกิจกรรมหรืออาสาสมัคร..." class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <button type="submit" class="bg-slate-800 text-white px-5 py-2 rounded-xl text-sm font-bold">ค้นหา</button>
                        </form>
                    </div>

                    <table class="w-full text-left min-w-[600px]">
                        <thead>
                            <tr class="text-[11px] font-bold text-slate-400 uppercase border-b border-slate-50">
                                <th class="pb-4 px-4">ชื่อกิจกรรม</th>
                                <th class="pb-4 px-4">วันที่</th>
                                <th class="pb-4 px-4 text-center text-orange-500">ยังไม่ตอบรับ</th>
                                <th class="pb-4 px-4 text-center text-emerald-500">เข้าร่วมแล้ว</th>
                                <th class="pb-4 px-4 text-center text-rose-500">ยกเลิกแล้ว</th>
                                <th class="pb-4 px-4 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php 
                            $count = 0;
                            while($row = $res_summary->fetch_assoc()): 
                                $count++;
                                $hidden_class = ($count > 3) ? 'event-row hidden-event' : 'event-row';
                            ?>
                            <tr class="<?= $hidden_class ?> hover:bg-slate-50/50 transition-all">
                                <td class="py-4 px-4 font-bold text-slate-700"><?= $row['event_name'] ?></td>
                                <td class="py-4 px-4 text-xs text-slate-500"><?= thai_date_short($row['event_date']) ?></td>
                                <td class="py-4 px-4 text-center"><div class="inline-flex circle-num bg-orange-50 text-orange-600"><?= ($row['pending_count'] < 0) ? 0 : $row['pending_count'] ?></div></td>
                                <td class="py-4 px-4 text-center"><div class="inline-flex circle-num bg-emerald-50 text-emerald-600"><?= $row['confirmed_count'] ?></div></td>
                                <td class="py-4 px-4 text-center"><div class="inline-flex circle-num bg-rose-50 text-rose-600"><?= $row['cancelled_count'] ?></div></td>
                                <td class="py-4 px-4 text-right">
                                    <button onclick="filterByEvent(<?= $row['event_id'] ?>, '<?= $row['event_name'] ?>')" class="px-4 py-1.5 bg-blue-600 text-white text-[11px] font-bold rounded-lg shadow-sm">ดูรายชื่อ</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php if($count > 3): ?>
                        <button onclick="toggleEvents()" class="w-full mt-4 text-[11px] font-bold text-blue-600 py-2 border-t border-dashed border-slate-100">ดูทั้งหมด / ซ่อน</button>
                    <?php endif; ?>
                </div>

                

                <div id="volunteer-section" class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-white">
                        <div>
                            <h3 id="v-title" class="font-bold text-slate-800">รายละเอียดการเข้าร่วมกิจกรรม</h3>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">แสดงเฉพาะสมาชิกที่มีสถานะ Active</p>
                        </div>
                        <button id="btn-show-all-v" onclick="resetVolunteerFilter()" class="hidden text-xs font-bold text-blue-600 hover:underline">
                            <i class="fa-solid fa-rotate-left mr-1"></i> แสดงทั้งหมด
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[800px]">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                    <th class="px-8 py-4">ข้อมูลอาสาสมัคร</th>
                                    <th class="px-8 py-4">กิจกรรม</th>
                                    <th class="px-8 py-4 text-center">สถานะเข้าร่วม</th>
                                </tr>
                            </thead>
                            <tbody id="volunteer-table-body" class="divide-y divide-slate-100">
                                <?php 
                                $v_count = 0; 
                                $result_list->data_seek(0); // รีเซ็ต pointer เพื่อให้ดึงข้อมูลจาก loop แรกได้ใหม่
                                while ($record = $result_list->fetch_assoc()): 
                                    $v_count++;
                                    $v_hidden_class = ($v_count > 5) ? 'v-row-hidden hidden' : '';
                                ?>
                                <tr class="v-row transition-all <?= $v_hidden_class ?>" data-eid="<?= $record['event_id'] ?>">
                                    <td class="px-8 py-4">
                                        <div class="flex items-center gap-3">
                                            <img src="<?= $record['profile_image_path'] ?: 'uploads/profiles/default.png' ?>" class="w-10 h-10 rounded-full object-cover border border-slate-100 shadow-sm">
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm"><?= $record['first_name'].' '.$record['last_name'] ?></div>
                                                <div class="text-[10px] text-slate-400 font-bold">ID: #<?= $record['user_pk_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4 text-sm text-slate-500 font-medium"><?= $record['event_name'] ?></td>
                                    <td class="px-8 py-4 text-center">
                                        <?php 
                                            $s = $record['reg_status'];
                                            if (is_null($s) || ($s != '1' && $s != '0')) {
                                                echo '<span class="status-pill bg-orange-100 text-orange-600 border border-orange-200">ยังไม่ตอบรับ</span>';
                                            } elseif ($s == '1') {
                                                echo '<span class="status-pill bg-emerald-100 text-emerald-600 border border-emerald-200">เข้าร่วมแล้ว</span>';
                                            } elseif ($s == '0') {
                                                echo '<span class="status-pill bg-rose-100 text-rose-600 border border-rose-200">ยกเลิกแล้ว</span>';
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if($v_count > 5): ?>
                    <div class="p-4 border-t border-slate-50 bg-slate-50/30 text-center">
                        <button id="toggle-v-btn" onclick="toggleVolunteerRows()" class="text-xs font-bold text-slate-500 hover:text-blue-600 transition-colors">
                            <i class="fa-solid fa-eye mr-1"></i> แสดงรายละเอียดเพิ่มเติม (อีก <?= $v_count - 5 ?> รายการ)
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
    // ฟังก์ชันซ่อน/เปิด รายชื่อในตารางรายละเอียดรายคน
    function toggleVolunteerRows() {
        const hiddenRows = document.querySelectorAll('.v-row-hidden');
        const btn = document.getElementById('toggle-v-btn');
        const isShowing = btn.innerHTML.includes('ซ่อน');

        hiddenRows.forEach(row => {
            row.classList.toggle('hidden');
        });

        if (isShowing) {
            btn.innerHTML = `<i class="fa-solid fa-eye mr-1"></i> แสดงรายละเอียดเพิ่มเติม`;
            document.getElementById('volunteer-section').scrollIntoView({ behavior: 'smooth' });
        } else {
            btn.innerHTML = `<i class="fa-solid fa-eye-slash mr-1"></i> ซ่อนรายละเอียด`;
        }
    }

    // ฟังก์ชันซ่อน/เปิด ตารางกิจกรรม
    function toggleEvents() {
        const rows = document.querySelectorAll('.event-row.hidden-event');
        rows.forEach(r => {
            r.style.display = (r.style.display === 'table-row') ? 'none' : 'table-row';
        });
    }

    // ฟังก์ชันกรองรายชื่อตามกิจกรรม
    function filterByEvent(eid, name) {
        const rows = document.querySelectorAll('.v-row');
        const btnToggle = document.getElementById('toggle-v-btn');
        
        // ถ้ากดฟิลเตอร์ ให้โชว์แถวที่ตรงกันทั้งหมด (ไม่สนว่าจะเคยโดนซ่อนด้วย toggle หรือไม่)
        rows.forEach(r => {
            if (r.getAttribute('data-eid') == eid) {
                r.style.display = 'table-row';
                r.classList.remove('hidden'); 
            } else {
                r.style.display = 'none';
            }
        });
        
        // ซ่อนปุ่ม "ดูเพิ่มเติม" เมื่อมีการฟิลเตอร์
        if(btnToggle) btnToggle.parentElement.classList.add('hidden');

        document.querySelector('#v-title').innerHTML = `กิจกรรม: <span class="text-blue-600">${name}</span>`;
        document.querySelector('#btn-show-all-v').classList.remove('hidden');
        document.querySelector('#volunteer-section').scrollIntoView({ behavior: 'smooth' });
    }

    // ฟังก์ชันรีเซ็ตกลับเป็นค่าเริ่มต้น (โชว์ 5 รายการ)
    function resetVolunteerFilter() {
        const rows = document.querySelectorAll('.v-row');
        const btnToggle = document.getElementById('toggle-v-btn');

        rows.forEach((r, index) => {
            r.style.display = 'table-row';
            if (index >= 5) {
                r.classList.add('hidden');
            }
        });

        if(btnToggle) {
            btnToggle.parentElement.classList.remove('hidden');
            btnToggle.innerHTML = `<i class="fa-solid fa-eye mr-1"></i> แสดงรายละเอียดเพิ่มเติม`;
        }
        
        document.querySelector('#v-title').innerText = "รายละเอียดการเข้าร่วมกิจกรรม";
        document.querySelector('#btn-show-all-v').classList.add('hidden');
    }
   

function toggleVolunteerList(btn) {
    const items = document.querySelectorAll('.extra-item');

    if (items.length === 0) return; // ✅ FIX กัน error

    const isHidden = items[0].classList.contains('hidden');

    items.forEach(el => {
        el.classList.toggle('hidden');
    });

    btn.innerText = isHidden ? 'ซ่อน' : 'แสดงทั้งหมด';
}

    </script>
</body>
</html>