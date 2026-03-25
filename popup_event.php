<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("connect.php");
$conn->query("SET time_zone = '+07:00'");

// ✅ เพิ่มแค่ตรงนี้
$show_popup = isset($_SESSION['show_popup']) && $_SESSION['show_popup'] === true;
unset($_SESSION['show_popup']);

// ── AJAX: คืน JSON ──────────────────────────────────────────────────────────
if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $event_id = (int)($_GET['event_id'] ?? 0);
    if ($event_id <= 0) { echo json_encode(['error' => 'invalid']); exit; }

    // ยอดรวม — นับจาก donations ที่ Approved จริง
    $row = $conn->query("
        SELECT
            COALESCE(SUM(eit.target_quantity), 0) AS total_target,
            COALESCE((
                SELECT SUM(d.quantity)
                FROM donations d
                WHERE d.event_id = $event_id AND d.status = 'Approved'
            ), 0) AS total_received,
            (SELECT COUNT(DISTINCT user_id)
             FROM donations
             WHERE event_id = $event_id AND status = 'Approved') AS donor_count
        FROM event_item_targets eit
        WHERE eit.event_id = $event_id
    ")->fetch_assoc();

    // รายการของ — นับยอดจาก donations แยกตาม item
    $items = [];
    $res = $conn->query("
        SELECT
            di.item_name,
            eit.target_quantity,
            COALESCE(SUM(CASE WHEN d.status = 'Approved' THEN d.quantity ELSE 0 END), 0) AS current_received
        FROM event_item_targets eit
        JOIN donation_items di ON eit.item_id = di.item_id
        LEFT JOIN donations d ON d.item_id = eit.item_id AND d.event_id = eit.event_id
        WHERE eit.event_id = $event_id
        GROUP BY eit.item_id, di.item_name, eit.target_quantity
        LIMIT 6
    ");
    while ($r = $res->fetch_assoc()) { $items[] = $r; }

    echo json_encode([
        'total_target'   => (int)$row['total_target'],
        'total_received' => (int)$row['total_received'],
        'donor_count'    => (int)$row['donor_count'],
        'items'          => $items,
    ]);
    exit;
}

// ── โหลดปกติ ────────────────────────────────────────────────────────────────
$result = $conn->query("
    SELECT e.*,
        COALESCE(SUM(eit.target_quantity), 0) AS total_target,
        COALESCE((
            SELECT SUM(d.quantity)
            FROM donations d
            WHERE d.event_id = e.event_id AND d.status = 'Approved'
        ), 0) AS total_received,
        (SELECT COUNT(DISTINCT user_id)
         FROM donations
         WHERE event_id = e.event_id AND status = 'Approved') AS donor_count
    FROM events e
    LEFT JOIN event_item_targets eit ON eit.event_id = e.event_id
    WHERE e.status = 'Active' AND e.is_active = 1
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
    LIMIT 1
");
$event = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
$mode  = $event ? 'event' : 'news';

// ── รายการของ — ยอดจาก donations ────────────────────────────────────────────
$items_detail = [];
if ($mode === 'event') {
    $eid = (int)$event['event_id'];
    $res = $conn->query("
        SELECT
            di.item_name,
            eit.target_quantity,
            COALESCE(SUM(CASE WHEN d.status = 'Approved' THEN d.quantity ELSE 0 END), 0) AS current_received
        FROM event_item_targets eit
        JOIN donation_items di ON eit.item_id = di.item_id
        LEFT JOIN donations d ON d.item_id = eit.item_id AND d.event_id = eit.event_id
        WHERE eit.event_id = $eid
        GROUP BY eit.item_id, di.item_name, eit.target_quantity
        LIMIT 6
    ");
    while ($row = $res->fetch_assoc()) { $items_detail[] = $row; }
}

$pct = ($event && $event['total_target'] > 0)
    ? min(100, round(($event['total_received'] / $event['total_target']) * 100))
    : 0;

$formatted_date = '';
if ($event) {
    $d = new DateTime($event['event_date']);
    $m = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $formatted_date = $d->format('j') . ' ' . $m[(int)$d->format('n')] . ' ' . ($d->format('Y') + 543);
}

$self_url = $_SERVER['SCRIPT_NAME'];
?>
<link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@200;300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600&family=Sarabun:wght@700&display=swap" rel="stylesheet">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Anuphan:wght@200;300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600&family=Sarabun:wght@700&display=swap');

    /* กัน popup บล็อกคลิก */
#tj-popup {
    pointer-events: none;
}

#tj-popup.active {
    pointer-events: auto;
}

    #tj-popup {
    all: initial;
    position: fixed; inset: 0; z-index: 2147483647;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Kanit', sans-serif !important;
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    opacity: 0; visibility: hidden;
    transition: 0.5s ease;
}
#tj-popup.active { opacity: 1; visibility: visible; }
#tj-popup .card {
    all: initial;
    background: #fff;
    width: 85%; max-width: 680px; max-height: 80vh;
    border-radius: 32px; overflow: hidden;
    font-family: 'Kanit', sans-serif !important;
    box-shadow: 0 20px 50px rgba(0,0,0,0.1);
    border: 4px solid rgba(255,255,255,0.9);
    transform: scale(0.95) translateY(20px);
    transition: 0.6s cubic-bezier(0.2,0.8,0.2,1);
    position: relative; display: flex;
}
#tj-popup.active .card { transform: scale(1) translateY(0); }
#tj-popup .img-side {
    width: 40%; position: relative; overflow: hidden; flex-shrink: 0;
}
#tj-popup .img-side img { width: 100%; height: 100%; object-fit: cover; display: block; }
#tj-popup .img-side::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to right, transparent 75%, #fff 100%);
}
#tj-popup .body-side {
    width: 60%; padding: 28px 32px;
    display: flex; flex-direction: column; justify-content: center;
    overflow-y: auto; position: relative; background: #fff;
}
#tj-popup .x {
    position: absolute; top: 18px; right: 22px;
    width: 30px; height: 30px; border-radius: 50%;
    background: #f1f5f9; border: none; cursor: pointer;
    font-size: 14px; color: #94a3b8;
    display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
#tj-popup .x:hover { background: #e2e8f0; color: #334155; }
#tj-popup .shimmer {
    font-weight: 800; font-size: 1.5rem; line-height: 1.1;
    background: linear-gradient(90deg, #0f172a, #3b82f6, #f97316, #0f172a);
    background-size: 200% auto;
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    animation: sh 4s linear infinite; margin-bottom: 4px;
}
@keyframes sh { to { background-position: 200% center; } }
#tj-popup .ename {
    font-size: 1.15rem; font-weight: 800; color: #0f172a;
    margin-bottom: 8px; display: block;
    font-family: 'Kanit', sans-serif !important;
}
#tj-popup .meta { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
#tj-popup .tag {
    background: #f8fafc; border: 1px solid #e2e8f0;
    padding: 4px 10px; border-radius: 10px;
    font-size: 11px; color: #64748b;
    display: flex; align-items: center; gap: 5px;
}
#tj-popup .live {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; color: #22c55e; margin-bottom: 6px;
}
#tj-popup .live::before {
    content: ''; width: 7px; height: 7px; border-radius: 50%; background: #22c55e;
    animation: lp 1.5s ease-in-out infinite;
}
@keyframes lp { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.7)} }
#tj-popup .stats { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 4px; }
#tj-popup .recv { font-size: 1.4rem; font-weight: 800; color: #002d62; line-height: 1; }
#tj-popup .recv small { font-size: 0.8rem; font-weight: 400; color: #94a3b8; margin: 0 3px; }
#tj-popup .pct { font-size: 1.2rem; font-weight: 800; color: #f97316; }
#tj-popup .prog-track {
    height: 10px; background: #f1f5f9; border-radius: 50px;
    overflow: visible;
    margin: 30px 0 5px; position: relative;
}
#tj-popup .prog-fill {
    height: 100%; width: 0%; border-radius: 50px;
    background: linear-gradient(90deg, #f97316, #fbbf24);
    transition: width 1.2s ease-out;
    box-shadow: 0 2px 8px rgba(249,115,22,0.25);
    position: relative; overflow: hidden;
}
#tj-popup .prog-fill::after {
    content: ''; position: absolute;
    right: -1px; top: 50%; transform: translateY(-50%);
    width: 10px; height: 10px; border-radius: 50%;
    background: #f97316;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #f97316;
}
#tj-popup .prog-fill::before {
    content: ''; position: absolute;
    inset: 0; border-radius: 50px;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.45) 50%, transparent 100%);
    background-size: 200% 100%;
    animation: tj-shimmer 1.8s ease-in-out infinite;
}
@keyframes tj-shimmer {
    0%   { background-position: -200% center; }
    100% { background-position:  200% center; }
}
#tj-popup .prog-sub {
    display: flex; justify-content: space-between;
    font-size: 11px; color: #94a3b8; margin-bottom: 16px;
}
#tj-popup .ilabel {
    font-size: 10px; font-weight: 600; letter-spacing: 2px;
    text-transform: uppercase; color: #94a3b8; margin-bottom: 8px; display: block;
}
#tj-popup .igrid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 12px; }
#tj-popup .icard {
    background: #fdfdfd; border: 1px solid #f1f5f9;
    border-radius: 12px; padding: 8px 11px;
    display: flex; justify-content: space-between; align-items: center;
    font-size: 12px; color: #334155;
    transition: border-color 0.4s, background 0.4s;
}
#tj-popup .icard.flash { border-color: #fbbf24; background: #fffbeb; }
#tj-popup .icard .iname { font-weight: 600; }
#tj-popup .icard .ival  { color: #f97316; font-weight: 800; }
#tj-popup .donor {
    font-size: 12px; color: #94a3b8; margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}
#tj-popup .donor strong { color: #334155; }
#tj-popup .btn {
    background: #002d62; color: #fff; border: none;
    padding: 12px; border-radius: 14px;
    font-size: 0.9rem; font-weight: 700; cursor: pointer;
    text-align: center; text-decoration: none; display: block;
    transition: 0.3s; box-shadow: 0 8px 16px rgba(0,45,98,0.12);
    font-family: 'Kanit', sans-serif !important;
}
#tj-popup .btn:hover { background: #0f172a; transform: translateY(-2px); }
#tj-popup .updated-at {
    text-align: center; font-size: 10px; color: #cbd5e1; margin-top: 10px;
}
@media (max-width: 800px) {
    #tj-popup .card { flex-direction: column; width: 95%; max-height: 92vh; }
    #tj-popup .img-side { width: 100%; height: 180px; flex-shrink: 0; }
    #tj-popup .body-side { width: 100%; padding: 24px; }
}
</style>

<div id="tj-popup">
<?php if ($mode === 'event'): ?>
    <div class="card">
        <div class="img-side">
            <img src="img/<?php echo htmlspecialchars($event['event_image']); ?>"
                 alt="<?php echo htmlspecialchars($event['event_name']); ?>">
        </div>
        <div class="body-side">
            <button onclick="tjClose()" class="x">✕</button>
            <div class="shimmer">เปิดรับบริจาคแล้ว !!</div>
            <span class="ename"><?php echo htmlspecialchars($event['event_name']); ?></span>
            <div class="meta">
                <div class="tag">📍 <?php echo htmlspecialchars($event['Location']); ?></div>
                <div class="tag">📅 <?php echo $formatted_date; ?></div>
            </div>
            <div class="live">บริจากเพื่อช่วยสังคม</div>
            <div class="stats">
                <div class="recv" id="tj-recv">
                    <?php echo number_format($event['total_received']); ?>
                    <small>/ <?php echo number_format($event['total_target']); ?></small> กิโลกรัม
                </div>
                <div class="pct" id="tj-pct"><?php echo $pct; ?>%</div>
            </div>
            <div class="prog-track">
                <div class="prog-fill" id="tj-bar"></div>
            </div>
            <div class="prog-sub">
                <span>เริ่มต้น</span>
                <span id="tj-remain">ขาดอีก <?php echo number_format(max(0, $event['total_target'] - $event['total_received'])); ?> กิโลกรัม</span>
            </div>
            <span class="ilabel">รายการสิ่งของ</span>
            <div class="igrid" id="tj-igrid">
                <?php foreach ($items_detail as $it): ?>
                <div class="icard">
                    <span class="iname"><?php echo htmlspecialchars($it['item_name']); ?></span>
                    <span class="ival"><?php echo number_format($it['current_received']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="donor">
                👥 <strong id="tj-donors"><?php echo number_format($event['donor_count']); ?> คน</strong>&nbsp;ร่วมบริจาคแล้ว
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="card" style="max-width:460px;display:block;text-align:center;padding:50px 40px;">
        <button onclick="tjClose()" class="x">✕</button>
        <div class="shimmer" style="font-size:2.2rem;">Coming Soon</div>
        <img src="img/popup.gif" style="width:100%;border-radius:24px;margin:20px 0;">
        <p style="color:#64748b;font-size:14px;margin-bottom:24px;font-family:'Kanit',sans-serif;">เรากำลังจัดเตรียมกิจกรรมดีๆ เพื่อสังคม</p>
        <button onclick="tjClose()" class="btn">ตกลง</button>
    </div>
<?php endif; ?>
</div>
<script>
(function () {

    var popup = document.getElementById('tj-popup');
    if (!popup) return;

    var card = popup.querySelector('.card');
    var bar  = document.getElementById('tj-bar');

    var EVENT_ID = <?php echo $mode === 'event' ? (int)$event['event_id'] : 0; ?>;
    var TARGET   = <?php echo $mode === 'event' ? (int)$event['total_target'] : 0; ?>;
    var API      = '<?php echo htmlspecialchars($self_url, ENT_QUOTES); ?>?json=1&event_id=' + EVENT_ID;

    // =========================
    // ✅ เปิด popup อัตโนมัติ
    // =========================
    function openPopup() {
        popup.classList.add('active');
        popup.style.pointerEvents = 'auto';

        if (bar) {
            setTimeout(function () {
                bar.style.width = '<?php echo $pct; ?>%';
            }, 300);
        }
    }

var SHOULD_SHOW_LOGIN = <?php echo $show_popup ? 'true' : 'false'; ?>;

// เช็คว่าเคยแสดง popup แล้วหรือยัง (ฝั่ง browser)
var alreadyShown = localStorage.getItem('popup_shown');

if (SHOULD_SHOW_LOGIN || !alreadyShown) {

    setTimeout(openPopup, 400);

    // บันทึกว่าเคยโชว์แล้ว
    localStorage.setItem('popup_shown', 'true');
}


    // =========================
    // ✅ ปิด popup (คลิกตรงไหนก็หาย)
    // =========================
    function closePopup() {
        popup.classList.remove('active');

        // delay เพื่อไม่ให้บล็อกคลิก
        setTimeout(function () {
            popup.style.pointerEvents = 'none';
            popup.style.display = 'none'; // 👈 ตัวสำคัญ แก้ทับหน้าอื่น
        }, 300);

        if (timer) clearInterval(timer);
    }

    window.tjClose = closePopup;

    // คลิกตรงไหนก็ปิด (ทั้งพื้นหลัง + card)
    popup.addEventListener('click', closePopup);


    // =========================
    // ✅ format
    // =========================
    function fmt(n) {
        return Number(n).toLocaleString('th-TH');
    }


    // =========================
    // ✅ AJAX อัปเดต
    // =========================
    function refresh() {

        if (!EVENT_ID) return;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', API + '&_=' + Date.now(), true);

        xhr.onload = function () {

            if (xhr.status !== 200) return;

            var d;
            try {
                d = JSON.parse(xhr.responseText);
            } catch (e) {
                return;
            }

            if (!d || d.error) return;

            var pct = TARGET > 0
                ? Math.min(100, Math.round((d.total_received / TARGET) * 100))
                : 0;

            var recvEl = document.getElementById('tj-recv');
            var pctEl  = document.getElementById('tj-pct');
            var barEl  = document.getElementById('tj-bar');
            var donorEl= document.getElementById('tj-donors');
            var remEl  = document.getElementById('tj-remain');

            if (recvEl)
                recvEl.innerHTML = fmt(d.total_received) +
                    ' <small>/ ' + fmt(TARGET) + '</small> กิโลกรัม';

            if (pctEl) pctEl.textContent = pct + '%';
            if (barEl) barEl.style.width = pct + '%';
            if (donorEl) donorEl.textContent = fmt(d.donor_count) + ' คน';

            if (remEl)
                remEl.textContent =
                    'ขาดอีก ' + fmt(Math.max(0, TARGET - d.total_received)) + ' กิโลกรัม';

            // update items
            if (d.items) {
                var cards = document.querySelectorAll('#tj-igrid .icard');

                d.items.forEach(function (item, i) {

                    if (!cards[i]) return;

                    var el = cards[i].querySelector('.ival');
                    if (!el) return;

                    var old = el.textContent.replace(/[^0-9]/g, '');

                    if (old !== String(item.current_received)) {
                        el.textContent = fmt(item.current_received);

                        cards[i].classList.add('flash');

                        setTimeout(function () {
                            cards[i].classList.remove('flash');
                        }, 1200);
                    }
                });
            }
        };

        xhr.send();
    }


    // =========================
    // ✅ Timer
    // =========================
    var timer = null;

    if (EVENT_ID) {
        timer = setInterval(refresh, 30000);
        setTimeout(refresh, 800);
    }


    // =========================
    // ✅ กัน popup บล็อกหน้า (สำคัญมาก)
    // =========================
    setTimeout(function () {
        if (!popup.classList.contains('active')) {
            popup.style.pointerEvents = 'none';
            popup.style.display = 'none';
        }
    }, 1500);

})();
</script>