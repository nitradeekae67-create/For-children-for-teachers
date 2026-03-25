<?php
session_start();
include('connect.php');

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// ตั้งค่า Timezone ให้ตรงกับไทยเพื่อความแม่นยำในการเช็ค 3 วัน
date_default_timezone_set('Asia/Bangkok');

$user_pk_id = $_SESSION['user_id']; 
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}
$conn->set_charset("utf8mb4");

// --- Fetch User Data ---
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_pk_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc(); 

// --- Fetch Events (ดึงประวัติการจอง) ---
$sql_events = "SELECT e.*, IFNULL(a.status, -1) as current_status 
                FROM events e 
                INNER JOIN join_event a ON e.event_id = a.event_id 
                WHERE a.user_id = ? 
                ORDER BY e.event_date DESC";
$stmt_ev = $conn->prepare($sql_events);
$stmt_ev->bind_param("i", $user_pk_id);
$stmt_ev->execute();
$upcoming_events = $stmt_ev->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Thai Date Formatter ---
function formatThaiDate($dateStr) {
    if (!$dateStr) return '-';
    $d = new DateTime($dateStr);
    $th_m = [1=>'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return $d->format('j') . " " . $th_m[(int)$d->format('m')] . " " . ($d->format('Y') + 543);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Profile | Volunteer Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
       :root {
            --glass-bg: rgba(255, 255, 255, 0.82);
            --glass-border: rgba(255, 255, 255, 0.6);
            --accent: #19729c; 
            --accent-gradient: linear-gradient(135deg, #19729c 0%, #4facfe 100%);
            --warm-orange: #d97706; 
        }

        body {
            font-family: 'Plus Jakarta Sans', 'IBM Plex Sans Thai', sans-serif;
            background-color: #f0fdfa;
            background-image: 
                radial-gradient(at 0% 0%, #fef3c7 0, transparent 40%), 
                radial-gradient(at 100% 0%, #ccfbf1 0, transparent 50%), 
                radial-gradient(at 50% 100%, #ffffff 0, transparent 60%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .main-wrapper {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 40px;
            margin: 50px auto;
            padding: 40px;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.05);
            opacity: 0; 
        }

        .event-glass-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            transition: 0.4s;
            border-left: 5px solid var(--accent);
            opacity: 0; transform: translateY(20px);
        }

        .date-badge {
            background: var(--accent-gradient);
            color: white;
            border-radius: 18px;
            min-width: 85px;
            padding: 15px 10px;
            text-align: center;
            margin-right: 25px;
        }

        .btn-cancel-glass {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            transition: 0.3s;
        }
        .btn-cancel-glass:hover:not(:disabled) { background: #fee2e2; color: #ef4444; border-color: #fecaca; }

        .days-left-tag {
            font-size: 0.85rem;
            color: #b45309;
            background: #fffbeb;
            padding: 4px 12px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 5px;
            border: 1px solid #fef3c7;
        }
    </style>
</head>
<body>
<?php include "menu_volunteer.php";?>

<div class="container">
    <div class="main-wrapper shadow-lg">
        <div class="row g-5">
            <div class="col-lg-4 text-center">
                <div class="profile-card">
                    <div class="avatar-outer" style="width:160px; height:160px; margin:auto; border:2px dashed #ccc; border-radius:50%; padding:5px;">
                        <div class="avatar-inner" style="width:100%; height:100%; border-radius:50%; overflow:hidden;">
                            <img src="<?php echo htmlspecialchars($user_data['profile_image_path'] ?? 'uploads/profiles/default.png'); ?>" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                    </div>
                    <h2 class="mt-3" style="font-weight: 800; color: #064e3b;"><?php echo htmlspecialchars($user_data['first_name']); ?></h2>
                    <p class="text-muted">จิตอาสา✨</p>
                    
                    <div class="mt-4 text-start">
                        <div class="p-3 mb-2 bg-white border rounded-4">
                            <small class="text-success fw-bold d-block text-uppercase" style="font-size:0.7rem;">อีเมล</small>
                            <span style="font-size:0.9rem;"><?php echo htmlspecialchars($user_data['email']); ?></span>
                        </div>
                        <div class="p-3 bg-white border rounded-4">
                            <small class="text-success fw-bold d-block text-uppercase" style="font-size:0.7rem;">รหัสสมาชิก</small>
                            <span style="font-size:0.9rem;">#<?php echo str_pad($user_data['id'] ?? 0, 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                    
                     <a href="history.php" class="btn btn-primary rounded-pill fw-bold mt-4 w-100 py-2 shadow-sm">
                        <i class="fas fa-history me-2"></i> ตรวจสอบประวัติกิจกรรม
                    </a>
                    <a href="logout.php" class="btn btn-link text-danger fw-bold mt-3 d-block">ออกจากระบบ</a>
                </div>
            </div>

            <div class="col-lg-8 ps-lg-5">
                <h2 class="fw-bold mb-4" style="color: var(--warm-orange);">
                    <i class="fas fa-leaf me-2"></i> ประวัติการเข้าร่วมกิจกรรม
                </h2>

                <?php if (count($upcoming_events) > 0): ?>
                    <?php foreach ($upcoming_events as $ev): 
                        $eventDate = new DateTime($ev['event_date']);
                        $eventDate->setTime(0,0,0);
                        $today = new DateTime('today');
                        
                        // คำนวณวันที่เหลือ
                        $diff = $today->diff($eventDate);
                        $daysUntilEvent = (int)$diff->format("%r%a");
                        $cancelDeadlineDays = $daysUntilEvent - 3; // ต้องยกเลิกก่อน 3 วัน

                        $isCancelled = ($ev['current_status'] == 0); 
                        $canCancel = (!$isCancelled && $daysUntilEvent >= 3);
                    ?>
                        <div class="event-glass-card <?php echo $isCancelled ? 'opacity-75' : ''; ?>">
                            <div class="date-badge">
                                <span class="fs-2 fw-bold d-block"><?php echo $eventDate->format('d'); ?></span>
                                <span class="d-block text-uppercase small"><?php echo $eventDate->format('M'); ?></span>
                                <small><?php echo $eventDate->format('Y')+543; ?></small>
                            </div>
                            
                            <div class="event-info flex-grow-1">
                                <h3 class="fw-bold mb-1 <?php echo $isCancelled ? 'text-decoration-line-through text-muted' : ''; ?>">
                                    <?php echo htmlspecialchars($ev['event_name']); ?>
                                </h3>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt me-1 text-primary"></i> <?php echo htmlspecialchars($ev['Location']); ?>
                                </div>

                                <?php if (!$isCancelled): ?>
                                    <?php if ($canCancel): ?>
                                        <div class="days-left-tag">
                                            <i class="far fa-clock me-1"></i>
                                            ระยะเวลาคงเหลือสำหรับการยกเลิก <b><?php echo $cancelDeadlineDays; ?> วัน</br> (สามารถดำเนินการยกเลิกได้ล่วงหน้าไม่น้อยกว่า 3 วัน)
                                        </div>
                                    <?php else: ?>
                                        <div class="text-danger small fw-bold">
                                            <i class="fas fa-lock me-1"></i> กิจกรรมได้เริ่มดำเนินการแล้ว 
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="mt-2">
                                    <?php if ($isCancelled): ?>
                                        <span class="badge rounded-pill bg-danger">ยกเลิกแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25">ยืนยันการเข้าร่วม</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="event-action ms-3">
                                <?php if ($isCancelled): ?>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>ยกเลิกแล้ว</button>
                                <?php elseif ($canCancel): ?>
                                    <button type="button" class="btn-cancel-glass" onclick="handleCancel(<?php echo $ev['event_id']; ?>)">
                                        ยกเลิก
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn-cancel-glass text-muted" disabled style="background:#eee; cursor:not-allowed;">
                                        <i class="fas fa-lock">สิ้นสุดระยะเวลากิจกรรม</i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <img src="https://cdn-icons-png.flaticon.com/512/6598/6598519.png" style="width:80px; opacity:0.2;" class="mb-3">
                        <p class="text-muted">ยังไม่มีประวัติการเข้าร่วมกิจกรรม</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
    window.onload = () => {
        gsap.to(".main-wrapper", { opacity: 1, duration: 0.8 });
        gsap.to(".event-glass-card", { opacity: 1, y: 0, stagger: 0.1, duration: 0.6 });
    };

    function handleCancel(eventId) {
        Swal.fire({
            title: 'ยืนยันการยกเลิก?',
            text: 'คุณต้องการยกเลิกการเข้าร่วมกิจกรรมนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ใช่, ฉันขอยกเลิก',
            cancelButtonText: 'ปิดหน้าต่าง'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('event_id', eventId);

                fetch('cancel_event.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('สำเร็จ!', 'คุณได้ยกเลิกกิจกรรมเรียบร้อยแล้ว', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('ล้มเหลว', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อระบบได้', 'error'));
            }
        });
    }
</script>
</body>
</html>