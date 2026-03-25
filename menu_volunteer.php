<?php
// เริ่ม Session หากยังไม่มีการเริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── ดึงข้อมูล user ล่าสุด (เพื่อให้ค่า status อัปเดตทันทีที่แอดมินกดยืนยัน) ──
$_menu_user = null;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $__id   = (int)$_SESSION['user_id'];
    $__stmt = $conn->prepare("SELECT profile_image_path, first_name, role, status FROM users WHERE id = ?");
    if ($__stmt) {
        $__stmt->bind_param("i", $__id);
        $__stmt->execute();
        $_menu_user = $__stmt->get_result()->fetch_assoc();
        
        // อัปเดตค่าใน Session ให้เป็นปัจจุบัน
        if ($_menu_user) {
            $_SESSION['role'] = $_menu_user['role'];
            $_SESSION['status'] = $_menu_user['status'];
        }
        $__stmt->close();
    }
}

// กำหนดตัวแปรสำหรับเช็คเงื่อนไข
$isLoggedIn  = isset($_SESSION['user_id']);
$userRole    = $_SESSION['role'] ?? '';
$userStatus  = $_SESSION['status'] ?? '';
$isVolunteer = ($userRole === 'volunteer');
$isApproved  = ($userStatus === 'active');
$isAdmin     = ($userRole === 'admin');

// Path รูปโปรไฟล์
$_avatar = 'uploads/profiles/default.png';
if (!empty($_menu_user['profile_image_path'])) {
    $_avatar = $_menu_user['profile_image_path'];
}
?>

<nav>
    <div class="container nav-con">
        <div class="nav-left-side">
            <div class="logo">
                <a href="index.php">
                    <img src="img/01.jpg" alt="โลโก้โครงการ" class="logo-img">
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">หน้าแรก</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="donate.php">บริจาคสิ่งของ</a></li>
                <?php endif; ?>

                <?php if ($isVolunteer): ?>
                    <li>
                        <?php if ($isApproved): ?>
                            <a href="events.php"><b>เข้าร่วมกิจกรรม</b></a>
                        <?php else: ?>
                            <span class="menu-locked">
                                <i class="fas fa-lock lock-icon"></i> เข้าร่วมกิจกรรม (รอตรวจสอบจิตอาสา)
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="nav-right-side">
            <div class="auth-menu">
                <?php if ($isLoggedIn): ?>
                    <div class="user-avatar-container">
                        <img src="<?php echo htmlspecialchars($_avatar); ?>"
                             alt="Avatar"
                             class="nav-avatar-img"
                             onerror="this.src='uploads/profiles/default.png'">
                    </div>

                    <a href="history.php" class="user-link">ประวัติสมาชิก</a>

                    <?php if ($isVolunteer): ?>
                        <span class="divider">|</span>
                        <?php if ($isApproved): ?>
                            <a href="user_profile.php"><b>ประวัติกิจกรรม</b></a>
                        <?php else: ?>
                            <span class="menu-locked text-xs">ประวัติกิจกรรม (รอตรวจสอบ)</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                        <span class="divider">|</span>
                        <a href="showuser.php"><b>สำหรับ Admin</b></a>
                    <?php endif; ?>

                    <span class="divider">|</span>
                    <a href="logout.php" class="btn-logout">ออกจากระบบ</a>

                <?php else: ?>
                    <a href="login.php" class="btn-login">เข้าสู่ระบบ</a>
                    <span class="divider">|</span>
                    <a href="register.php" class="user-link">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
/* ── ส่วนที่เพิ่ม/แก้ไขใหม่ ── */
.menu-locked {
    color: #94a3b8; /* สีเทาเข้ม */
    font-size: .95rem;
    font-weight: 500;
    cursor: not-allowed;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 0;
}

.lock-icon {
    font-size: 0.8rem;
    color: #cbd5e1;
}

.text-xs { font-size: 0.85rem; }

/* ── สไตล์เดิม ── */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Anuphan',sans-serif; line-height:1.7; letter-spacing:-.02em; }

nav {
    background: rgba(255,255,255,.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    height: 80px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,.04);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(0,0,0,.04);
}
.nav-con {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 25px;
}
.nav-left-side { display:flex; align-items:center; gap:50px; }
.logo img.logo-img { height:52px; width:auto; display:block; transition:transform .4s ease; }
.logo img.logo-img:hover { transform:scale(1.04); }

.nav-menu { display:flex; list-style:none; gap:30px; align-items: center; }
.nav-menu a {
    text-decoration: none;
    color: #64748b;
    font-size: .95rem;
    font-weight: 500;
    position: relative;
    transition: .3s;
}
.nav-menu a::after {
    content: '';
    position: absolute;
    width: 0; height: 2px;
    bottom: -5px; left: 0;
    background: #19729c;
    transition: width .3s ease;
}
.nav-menu a:hover { color:#19729c; }
.nav-menu a:hover::after { width:100%; }

.auth-menu {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    padding: 5px 5px 5px 18px;
    border-radius: 50px;
    border: 1px solid #edf2f7;
}
.auth-menu a { text-decoration:none; font-size:.85rem; color:#64748b; font-weight:500; }

.btn-login {
    background: linear-gradient(135deg,#19729c,#2196f3);
    color: #fff !important;
    padding: 8px 18px;
    border-radius: 50px;
    font-weight: 600 !important;
    transition: .3s;
    box-shadow: 0 4px 12px rgba(25,114,156,.25);
}
.btn-login:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(25,114,156,.35); }

.btn-logout {
    background: #fee2e2;
    color: #ef4444 !important;
    padding: 6px 15px;
    border-radius: 50px;
    transition: .3s;
}
.btn-logout:hover { background:#ef4444; color:#fff !important; }

.divider { color:#e2e8f0; }

.user-avatar-container {
    width: 36px; height: 36px;
    border-radius: 50%;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2.5px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
    flex-shrink: 0;
    background: #dbeafe;
}
.nav-avatar-img {
    width: 100%; height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
}
</style>