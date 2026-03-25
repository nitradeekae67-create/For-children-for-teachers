<?php
include('connect.php');
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&family=Sarabun:wght@700&display=swap" rel="stylesheet">

<style>
    /* 🎨 ใช้ตัวแปรสีสวยๆ ของแม่ทั้งหมด */
    :root {
        --primary: #10b981;
        --primary-dark: #047857;
        --primary-light: #d1fae5;
        --bg-sidebar: #ffffff;
        --bg-body: #f8fafc;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        --nav-hover: #f0fdf4;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    [data-theme="dark"] {
        --bg-sidebar: #111827;
        --bg-body: #757779;
        --text-main: #f1f5f9;
        --text-muted: #94a3b8;
        --border-color: #1f2937;
        --nav-hover: #1f2937;
        --primary-light: #064e3b;
    }

    /* 🏰 Sidebar - เพิ่ม Transition สำหรับการย่อ */
    .sidebar {
        width: 250px;
        height: 100vh;
        background: var(--bg-sidebar);
        padding: 20px 15px;
        position: fixed;
        top: 0;
        left: 0;
        border-right: 1px solid var(--border-color);
        z-index: 1000;
        font-family: 'IBM Plex Sans Thai', sans-serif;
        display: flex;
        flex-direction: column;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* 🛠️ สไตล์ตอนย่อเมนู (Collapsed) */
    .sidebar.collapsed {
        width: 80px;
        padding: 20px 10px;
    }

    .sidebar.collapsed .brand span,
    .sidebar.collapsed .menu-label,
    .sidebar.collapsed .nav-link span {
        display: none;
    }

    .sidebar.collapsed .brand-wrapper {
        justify-content: center;
        padding: 0;
    }

    .sidebar.collapsed .nav-link {
        justify-content: center;
        padding: 12px 0;
    }

    .sidebar.collapsed .nav-link i {
        margin: 0;
        font-size: 20px;
    }

    /* 🏷️ Brand & Buttons */
    .brand-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding: 0 10px;
    }

    .brand {
        font-size: 18px;
        font-weight: 800;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .button-group {
        display: flex;
        gap: 5px;
    }

    /* ปุ่มสไตล์เดียวกับที่แม่ชอบ */
    .theme-toggle, .toggle-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-sidebar);
        color: var(--text-main);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .theme-toggle:hover, .toggle-btn:hover {
        background: var(--nav-hover);
        color: var(--primary);
    }

    .menu-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 15px 0 8px 10px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        border-radius: 12px;
        color: var(--text-muted);
        text-decoration: none;
        margin-bottom: 4px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .nav-link:hover {
        background: var(--nav-hover);
        color: var(--primary);
    }

    .nav-link.active {
        background: var(--primary);
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }

    .logout-box {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }

    /* ขยับเนื้อหาหน้าหลักตาม Sidebar */
    @media (min-width: 992px) {
        .admin-layout {
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }
        .admin-layout.expanded {
            margin-left: 80px;
        }
    }
</style>

<aside class="sidebar" id="sidebar">
    <div class="brand-wrapper">
        <a href="dashboard.php" class="brand">
            <i class="fas fa-leaf"></i> 
            <span>Admin</span>
        </a>
        <div class="button-group">
            <button class="theme-toggle" id="theme-toggle" title="สลับโหมด">
                <i class="fas fa-moon" id="theme-icon"></i>
            </button>
            <button class="toggle-btn" id="toggle-sidebar" title="ย่อเมนู">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
    
    <div class="menu-label">แดชบอร์ด</div>
    <nav>
        <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> <span>หน้าหลัก</span>
        </a>
    </nav>

    <div class="menu-label">การจัดการสิ่งของ</div>
    <nav>
        <a href="admin_history.php" class="nav-link <?php echo ($current_page == 'admin_history.php') ? 'active' : ''; ?>">
            <i class="fas fa-hand-holding-heart"></i> <span>รายการบริจาค</span>
        </a>
        <a href="show_join_event.php" class="nav-link <?php echo ($current_page == 'show_join_event.php') ? 'active' : ''; ?>">
            <i class="fas fa-box-open"></i> <span>รายงานจิตอาสา</span>
        </a>
    </nav>

    <div class="menu-label">ระบบสมาชิก</div>
    <nav>
        <a href="showuser.php" class="nav-link <?php echo ($current_page == 'showuser.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> <span>ผู้ใช้งานทั้งหมด</span>
        </a>
    </nav>

    <div class="menu-label">เพิ่มข้อมูล</div> 
    <nav>
        <a href="manage_events.php" class="nav-link <?php echo ($current_page == 'manage_events.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> <span>จัดการกิจกรรม</span>
        </a>
        
        <a href="manage_news.php" class="nav-link <?php echo ($current_page == 'manage_news.php') ? 'active' : ''; ?>">
            <i class="fas fa-edit"></i> <span>แก้ไขข่าวสาร</span>
        </a>
    </nav>

    <div class="logout-box">
        <a href="index.php" class="nav-link">
            <i class="fas fa-home"></i> <span>หน้าแรก</span>
        </a>
        <a href="logout.php" class="nav-link" style="color: #ef4444;">
            <i class="fas fa-sign-out-alt"></i> <span>ออกจากระบบ</span>
        </a>
    </div>
</aside>

<script>
    // 🌗 ระบบสลับโหมดเดิมของแม่
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const body = document.documentElement;

    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        body.setAttribute('data-theme', 'dark');
        themeIcon.classList.replace('fa-moon', 'fa-sun');
    }

    themeToggle.addEventListener('click', () => {
        let theme = body.getAttribute('data-theme');
        if (theme === 'dark') {
            body.setAttribute('data-theme', 'light');
            themeIcon.classList.replace('fa-sun', 'fa-moon');
            localStorage.setItem('theme', 'light');
        } else {
            body.setAttribute('data-theme', 'dark');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
            localStorage.setItem('theme', 'dark');
        }
    });

    // ☰ ระบบย่อเมนูใหม่
    const toggleSidebar = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    const adminLayout = document.querySelector('.admin-layout');

    // โหลดสถานะล่าสุด
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
        if (adminLayout) adminLayout.classList.add('expanded');
    }

    toggleSidebar.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        if (adminLayout) adminLayout.classList.toggle('expanded');
        
        // บันทึกสถานะ
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebar-collapsed', isCollapsed);
    });
</script>