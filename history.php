<?php
session_start();
include('connect.php');
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_pk_id = $_SESSION['user_id']; 
$conn = new mysqli("localhost", "root", "", "project");
$conn->set_charset("utf8mb4");

// --- Logic อัปโหลดรูปโปรไฟล์ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image'])) {
    $target_dir = "uploads/profiles/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    $ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = $user_pk_id . '_' . time() . "." . $ext;
    $target_file = $target_dir . $new_filename;
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("UPDATE users SET profile_image_path = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $user_pk_id);
        $stmt->execute();
        header("Location: history.php?success=1"); exit();
    }
}

function get_profile_image($path, $name = "User") {
    if (!empty($path) && file_exists($path)) return $path . "?v=" . time();
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=f1f5f9&color=19729c&size=200&bold=true";
}

function formatThaiDate($dateStr) {
    if (empty($dateStr)) return '-';
    $d = new DateTime($dateStr);
    $th_m = [1=>'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return $d->format('j') . " " . $th_m[(int)$d->format('m')] . " " . ($d->format('Y') + 543);
}

$user_data = $conn->query("SELECT * FROM users WHERE id = $user_pk_id")->fetch_assoc();
$confirmed_events = $conn->query("SELECT T3.* FROM join_event T2 JOIN events T3 ON T2.event_id = T3.event_id WHERE T2.user_id = $user_pk_id AND T2.status = 1 ORDER BY T3.event_date DESC")->fetch_all(MYSQLI_ASSOC);

$res_don = $conn->query("SELECT d.*, e.event_name, i.item_name,
    CASE 
        WHEN d.status = 'Approved' THEN 'ยืนยันแล้ว' 
        WHEN d.status = 'Rejected' THEN 'ไม่ผ่านการอนุมัติ' 
        ELSE 'รอตรวจสอบ' 
    END AS status_text 
    FROM donations d 
    LEFT JOIN events e ON d.event_id = e.event_id 
    LEFT JOIN donation_items i ON d.item_id = i.item_id 
    WHERE d.user_id = $user_pk_id 
    ORDER BY d.donation_date DESC");

$my_donations = [];
while ($don = $res_don->fetch_assoc()) {
    $don['items'] = [['item_name' => $don['item_name'], 'quantity' => $don['quantity']]];
    $my_donations[] = $don;
}

// รายชื่อจังหวัดทั้งหมด
$provinces = ["กรุงเทพมหานคร","กระบี่","กาญจนบุรี","กาฬสินธุ์","กำแพงเพชร","ขอนแก่น","จันทบุรี","ฉะเชิงเทรา","ชลบุรี","ชัยนาท","ชัยภูมิ","ชุมพร","เชียงราย","เชียงใหม่","ตรัง","ตราด","ตาก","นครนายก","นครปฐม","นครพนม","นครราชสีมา","นครศรีธรรมราช","นครสวรรค์","นนทบุรี","นราธิวาส","น่าน","บึงกาฬ","บุรีรัมย์","ปทุมธานี","ประจวบคีรีขันธ์","ปราจีนบุรี","ปัตตานี","พระนครศรีอยุธยา","พะเยา","พังงา","พัทลุง","พิจิตร","พิษณุโลก","เพชรบุรี","เพชรบูรณ์","แพร่","ภูเก็ต","มหาสารคาม","มุกดาหาร","แม่ฮ่องสอน","ยโสธร","ยะลา","ร้อยเอ็ด","ระนอง","ระยอง","ราชบุรี","ลพบุรี","ลำปาง","ลำพูน","เลย","ศรีสะเกษ","สกลนคร","สงขลา","สตูล","สมุทรปราการ","สมุทรสงคราม","สมุทรสาคร","สระแก้ว","สระบุรี","สิงห์บุรี","สุโขทัย","สุพรรณบุรี","สุราษฎร์ธานี","สุรินทร์","หนองคาย","หนองบัวลำภู","อ่างทอง","อำนาจเจริญ","อุดรธานี","อุตรดิตถ์","อุทัยธานี","อุบลราชธานี"];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Anuphan:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
       :root {
            --glass-bg: rgba(255, 255, 255, 0.82);
            --glass-border: rgba(255, 255, 255, 0.6);
            --accent: #19729c; 
            --accent-dark: #0f4d6a;
            --accent-gradient: linear-gradient(135deg, #19729c 0%, #4facfe 100%);
            --warm-orange: #d97706; 
            --brand: #19729c;
            --brand-soft: rgba(25, 114, 156, 0.1);
            --text-title: #1e293b;
            --text-light: #64748b;
            --radius-xl: 40px; 
            --radius-lg: 30px; 
            --radius-md: 20px; 
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
            color: #1e293b;
        }

        .main-wrapper {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 48px;
            animation: appAppear 1s cubic-bezier(0.19, 1, 0.22, 1);
        }

        @keyframes appAppear {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(25, 114, 156, 0.1);
            border-radius: 40px !important; 
            padding: 48px 32px;
            text-align: center;
            height: fit-content;
            position: sticky;
            top: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .content-group-box {
            background: rgba(161, 200, 252, 0.5);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 40px !important;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .luxury-card {
            background: #ffffff;
            border-radius: 30px !important;
            padding: 32px;
            border: 1px solid rgba(241, 245, 249, 0.8);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.02);
        }

        .luxury-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(25, 114, 156, 0.1);
        }

        .profile-image {
            width: 160px; height: 160px; border-radius: 50px;
            object-fit: cover; border: 8px solid #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .cam-overlay {
            position: absolute; bottom: 0; right: 20px;
            background: var(--brand); width: 44px; height: 44px;
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            color: white; cursor: pointer; border: 4px solid #fff; transition: 0.3s;
        }

        .dash-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px; }
        .stat-item {
            background: #fff; padding: 20px 10px; border-radius: var(--radius-md);
            border: 1px solid #f1f5f9; transition: 0.3s;
        }

        .section-header {
            display: flex; align-items: center; gap: 16px; margin-bottom: 30px;
            border-bottom: 2px solid var(--brand-soft); padding-bottom: 15px;
        }

        .badge { padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 8px; }
        .badge-Approved { background: #e0f2fe; color: #0369a1; }
        .badge-Pending { background: #fff7ed; color: #c2410c; }
        .badge-Rejected { background: #fef2f2; color: #b91c1c; }

        .item-row { display: flex; flex-wrap: wrap; gap: 10px; margin: 24px 0; }
        .chip { background: #f8fafc; padding: 10px 20px; border-radius: 18px !important; font-size: 0.9rem; color: var(--text-title); font-weight: 600; border: 1px solid #f1f5f9; }
        .chip b { color: var(--warm-orange); margin-left: 8px; }

        .proof-img { width: 80px; height: 80px; border-radius: 20px; object-fit: cover; cursor: pointer; transition: 0.4s; border: 3px solid #fff; }
        .logout-link { text-decoration: none; color: #ef4444; font-weight: 700; padding: 16px; border-radius: 20px; display: block; text-align: center; }

        .btn-edit-action { background: #e0f2fe; color: #0369a1; border-radius: 12px; border: none; padding: 6px 15px; font-weight: 700; font-size: 0.8rem; transition: 0.3s; }
        .btn-cancel-action { background: #fee2e2; color: #b91c1c; border-radius: 12px; border: none; padding: 6px 15px; font-weight: 700; font-size: 0.8rem; transition: 0.3s; }
        .btn-edit-action:hover { background: #bae6fd; transform: scale(1.05); }
        .btn-cancel-action:hover { background: #fecaca; transform: scale(1.05); }

        /* ===== Tab Navigation ===== */
        .tab-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
            background: rgba(255,255,255,0.6);
            padding: 8px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.8);
        }
        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
            color: var(--text-light);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .tab-btn.active {
            background: var(--accent-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(25,114,156,0.3);
        }
        .tab-content { display: none; }
        .tab-content.active { display: flex; flex-direction: column; gap: 32px; }

        /* ===== Profile Edit Form ===== */
        .edit-profile-card {
            background: #ffffff;
            border-radius: 30px;
            padding: 36px;
            border: 1px solid rgba(241, 245, 249, 0.8);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.02);
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            font-size: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            color: var(--text-title);
            background: #f8fafc;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--brand);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(25,114,156,0.08);
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .section-divider {
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--brand);
            margin: 28px 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: var(--brand-soft);
            border-radius: 2px;
        }

        .btn-save {
            background: var(--accent-gradient);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 16px 32px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
            box-shadow: 0 8px 20px rgba(25,114,156,0.25);
            margin-top: 8px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(25,114,156,0.35);
        }

        .password-toggle {
            position: relative;
        }
        .password-toggle .toggle-eye {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
        }

        .hint-text {
            font-size: 0.78rem;
            color: var(--text-light);
            margin-top: 6px;
        }
    </style>
</head>
<body>

<?php include 'menu_volunteer.php'; ?>

<div class="main-wrapper">
    <aside class="sidebar">
        <div class="profile-container" style="position: relative; margin-bottom: 32px;">
            <img src="<?php echo get_profile_image($user_data['profile_image_path'], $user_data['first_name']); ?>" class="profile-image">
            <label for="p_input" class="cam-overlay">
                <i class="fas fa-camera"></i>
            </label>
        </div>
        
        <div class="user-info" style="margin-bottom: 20px;">
    <h2 style="font-weight: 800; margin-bottom: 10px;">
        <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
    </h2>

    <p style="color: var(--text-light); margin-bottom: 5px;">
        <strong>อีเมล:</strong> <?php echo htmlspecialchars($user_data['email']); ?>
    </p>
    
    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 5px;">
        <strong>เบอร์โทรศัพท์:</strong> <?php echo htmlspecialchars($user_data['phone'] ?: '-'); ?>
    </p>

    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 5px;">
        <strong>จังหวัด:</strong> <?php echo htmlspecialchars($user_data['province'] ?: '-'); ?>
    </p>

</div>

        <div class="dash-stats">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'volunteer'): ?>
            <div class="stat-item">
                <small style="display:block; font-size:0.65rem; font-weight:700; color:var(--brand); text-transform:uppercase;">กิจกรรม</small>
                <span style="font-weight:800; font-size:1.5rem;"><?php echo count($confirmed_events); ?></span>
            </div>
            <?php endif; ?>
            <div class="stat-item">
                <small style="display:block; font-size:0.65rem; font-weight:700; color:var(--brand); text-transform:uppercase;">การบริจาค</small>
                <span style="font-weight:800; font-size:1.5rem;"><?php echo count($my_donations); ?></span>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" style="display:none;">
            <input type="file" name="profile_image" id="p_input" onchange="this.form.submit()">
        </form>
        
        <a href="logout.php" class="logout-link">
            <i class="fas fa-power-off me-2"></i> ออกจากระบบ
        </a>
        
    </aside>

    <div class="content-section">

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('history')">
                <i class="fas fa-history me-2"></i>ประวัติของฉัน
            </button>
            <button class="tab-btn" onclick="switchTab('edit')">
                <i class="fas fa-user-edit me-2"></i>แก้ไขโปรไฟล์
            </button>
        </div>

        <!-- ===== TAB: ประวัติ ===== -->
        <div id="tab-history" class="tab-content active">

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'volunteer'): ?>
            <div class="content-group-box">
                <div class="section-header">
                    <div style="width:48px; height:48px; background:#fff; border-radius:16px; display:flex; align-items:center; justify-content:center; color:var(--brand);"><i class="fas fa-bolt"></i></div>
                    <h3>การเข้าร่วมกิจกรรม</h3>
                </div>
                <?php foreach($confirmed_events as $ev): ?>
                <div class="luxury-card" style="display:flex; align-items:center; justify-content:space-between;">
                    <div>
                        <div style="font-weight:700; font-size:1.15rem;"><?php echo htmlspecialchars($ev['event_name']); ?></div>
                        <div style="font-size:0.85rem; color:var(--text-light); font-weight:600;"><i class="far fa-calendar-check me-2"></i> <?php echo formatThaiDate($ev['event_date']); ?></div>
                    </div>
                    <div class="badge badge-Approved"><i class="fas fa-check-double"></i> สำเร็จแล้ว</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="content-group-box">
                <div class="section-header">
                    <div style="width:48px; height:48px; background:#fff; border-radius:16px; display:flex; align-items:center; justify-content:center; color:var(--brand);"><i class="fas fa-heart"></i></div>
                    <h3>ประวัติการบริจาค</h3>
                </div>

                <?php foreach($my_donations as $don): ?>
                <div class="luxury-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-weight:800; color:var(--brand); font-size:0.85rem; background:var(--brand-soft); padding:4px 12px; border-radius:8px;">#DON-<?php echo $don['donation_id']; ?></span>
                            <h4 style="margin:16px 0 6px; font-size:1.3rem; font-weight:800;"><?php echo htmlspecialchars($don['event_name'] ?: 'บริจาคทั่วไป'); ?></h4>
                            <p style="margin:0; font-size:0.85rem; color:var(--text-light);"><i class="far fa-clock me-1"></i> <?php echo formatThaiDate($don['donation_date']); ?></p>
                        </div>
                        <div style="text-align: right;">
                            <div class="badge badge-<?php echo $don['status']; ?>">
                                <i class="fas <?php echo $don['status'] == 'Approved' ? 'fa-certificate' : ($don['status'] == 'Rejected' ? 'fa-circle-xmark' : 'fa-hourglass-half'); ?>"></i>
                                <?php echo $don['status_text']; ?>
                            </div>
                            <?php if($don['status'] == 'Pending'): ?>
                            <div style="margin-top: 12px; display: flex; gap: 8px; justify-content: flex-end;">
                                <button onclick="editDonation(<?php echo $don['donation_id']; ?>, <?php echo $don['quantity']; ?>, '<?php echo htmlspecialchars($don['item_name']); ?>')" class="btn-edit-action">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                                <button onclick="cancelDonation(<?php echo $don['donation_id']; ?>)" class="btn-cancel-action">
                                    <i class="fas fa-trash-alt"></i> ยกเลิก
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="item-row">
                        <?php foreach($don['items'] as $it): ?>
                            <div class="chip"><?php echo htmlspecialchars($it['item_name']); ?> <b>x<?php echo $it['quantity']; ?></b></div>
                        <?php endforeach; ?>
                    </div>
                    <?php if($don['image_path_1']): ?>
                    <div style="margin-top: 24px;">
                        <img src="<?php echo $don['image_path_1']; ?>" class="proof-img" onclick="zoomImage(this.src)">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

        </div><!-- end tab-history -->

        <!-- ===== TAB: แก้ไขโปรไฟล์ ===== -->
        <div id="tab-edit" class="tab-content">
            <div class="content-group-box">
                <div class="section-header">
                    <div style="width:48px; height:48px; background:#fff; border-radius:16px; display:flex; align-items:center; justify-content:center; color:var(--brand);"><i class="fas fa-user-pen"></i></div>
                    <h3>แก้ไขข้อมูลส่วนตัว</h3>
                </div>

                <div class="edit-profile-card">
                    <form id="editProfileForm">

                        <div class="section-divider"><i class="fas fa-id-card"></i> ข้อมูลทั่วไป</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">ชื่อ</label>
                                <input type="text" name="first_name" class="form-input" value="<?= htmlspecialchars($user_data['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" name="last_name" class="form-input" value="<?= htmlspecialchars($user_data['last_name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" placeholder="0xx-xxx-xxxx">
                        </div>

                        <div class="section-divider"><i class="fas fa-map-marker-alt"></i> ที่อยู่</div>
                        <div class="form-group">
                            <label class="form-label">จังหวัด</label>
                            <select name="province" class="form-input">
                                <option value="">-- เลือกจังหวัด --</option>
                                <?php foreach($provinces as $pv): ?>
                                    <option value="<?= $pv ?>" <?= ($user_data['province'] === $pv) ? 'selected' : '' ?>><?= $pv ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ที่อยู่</label>
                            <textarea name="address" class="form-input" rows="3" placeholder="บ้านเลขที่ / ถนน / ตำบล / อำเภอ"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                        </div>

                        <div class="section-divider"><i class="fas fa-lock"></i> เปลี่ยนรหัสผ่าน <span style="font-size:0.75rem; color:var(--text-light); text-transform:none; font-weight:400;">(ไม่บังคับ)</span></div>
                        <div class="form-group">
                            <label class="form-label">รหัสผ่านปัจจุบัน</label>
                            <div class="password-toggle">
                                <input type="password" name="current_password" id="cur_pw" class="form-input" placeholder="กรอกรหัสผ่านปัจจุบัน" style="padding-right: 50px;">
                                <span class="toggle-eye" onclick="togglePw('cur_pw')"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">รหัสผ่านใหม่</label>
                                <div class="password-toggle">
                                    <input type="password" name="new_password" id="new_pw" class="form-input" placeholder="รหัสผ่านใหม่" style="padding-right: 50px;">
                                    <span class="toggle-eye" onclick="togglePw('new_pw')"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                <div class="password-toggle">
                                    <input type="password" name="confirm_password" id="con_pw" class="form-input" placeholder="ยืนยันรหัสผ่านใหม่" style="padding-right: 50px;">
                                    <span class="toggle-eye" onclick="togglePw('con_pw')"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <p class="hint-text"><i class="fas fa-info-circle me-1"></i> หากไม่ต้องการเปลี่ยนรหัสผ่าน ให้ปล่อยช่องรหัสผ่านว่างไว้</p>

                        <button type="submit" class="btn-save mt-4">
                            <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                        </button>
                    </form>
                </div>
            </div>
        </div><!-- end tab-edit -->

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ===== Tab Switch =====
    function switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    // ===== Toggle Password =====
    function togglePw(id) {
        const el = document.getElementById(id);
        el.type = el.type === 'password' ? 'text' : 'password';
    }

    // ===== Submit Edit Profile =====
    document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        // Validate รหัสผ่าน
        const newPw = formData.get('new_password');
        const conPw = formData.get('confirm_password');
        const curPw = formData.get('current_password');

        if (newPw || conPw || curPw) {
            if (!curPw) {
                return Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสผ่านปัจจุบันด้วย', 'warning');
            }
            if (newPw !== conPw) {
                return Swal.fire('แจ้งเตือน', 'รหัสผ่านใหม่ไม่ตรงกัน', 'warning');
            }
            if (newPw.length < 6) {
                return Swal.fire('แจ้งเตือน', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร', 'warning');
            }
        }

        Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch('update_profile.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', text: data.message, confirmButtonColor: '#19729c' })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
            }
        } catch(err) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้' });
        }
    });

    // ===== Zoom Image =====
    function zoomImage(url) {
        Swal.fire({ imageUrl: url, showConfirmButton: false, background: 'rgba(255,255,255,0.9)', backdrop: `blur(15px)`, showCloseButton: true });
    }

    // ===== Edit Donation =====
    function editDonation(id, currentQty, itemName) {
        Swal.fire({
            title: 'แก้ไขข้อมูลการบริจาค',
            html: `
                <div style="text-align: left; font-family: 'Anuphan', sans-serif; padding: 10px;">
                    <p style="margin-bottom: 15px;">รายการ: <strong style="color: #19729c;">${itemName}</strong></p>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">จำนวนที่บริจาค:</label>
                        <input type="number" id="swal-qty" class="swal2-input" value="${currentQty}" min="1" style="width: 100%; margin: 0;">
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">เปลี่ยนรูปหลักฐานใหม่ (ถ้ามี):</label>
                        <input type="file" id="swal-file" class="form-control" accept="image/*" style="display: block; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 10px;">
                        <small style="color: #64748b; margin-top: 5px; display: block;">*หากไม่ต้องการเปลี่ยนรูป ให้ปล่อยว่างไว้</small>
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'บันทึกการแก้ไข',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#19729c',
            preConfirm: () => {
                const qty = document.getElementById('swal-qty').value;
                const file = document.getElementById('swal-file').files[0];
                if (!qty || qty < 1) { Swal.showValidationMessage('กรุณาระบุจำนวนที่ถูกต้อง'); return false; }
                return { qty, file };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'edit');
                formData.append('id', id);
                formData.append('qty', result.value.qty);
                if (result.value.file) formData.append('new_proof', result.value.file);
                Swal.fire({ title: 'กำลังบันทึกข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                fetch('update_donation_action.php', { method: 'POST', body: formData })
                    .then(() => { window.location.href = 'history.php?success=edit'; })
                    .catch(() => Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error'));
            }
        });
    }

    // ===== Cancel Donation =====
    function cancelDonation(id) {
        Swal.fire({
            title: 'ยืนยันการยกเลิก?',
            text: "ข้อมูลการบริจาคนี้จะถูกลบออกจากระบบ",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ยืนยันการยกเลิก',
            cancelButtonText: 'กลับ',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `update_donation_action.php?action=cancel&id=${id}`;
            }
        });
    }

    <?php if(isset($_GET['success'])): ?>
    Swal.fire({ icon: 'success', title: 'สำเร็จ!', timer: 2000, showConfirmButton: false });
    <?php endif; ?>
</script>
<?php include 'footer.php'; ?>
</body>
</html>