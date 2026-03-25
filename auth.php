<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('connect.php');

function checkRole($roles = []) {
    // 1. ยังไม่ได้ล็อกอิน
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // 2. ไม่มีสิทธิ์เข้าถึง (บทบาทไม่อยู่ในรายการที่อนุญาต)
    if (!in_array($_SESSION['role'], $roles)) {
        // ถ้าเป็น Admin เข้าหน้านี้ไม่ได้ ให้แจ้งเตือน แต่ปกติ Admin ควรเข้าได้ทุกหน้า
        // สำหรับหน้านี้ให้เด้งกลับ index หรือแจ้งเตือน
        echo "<script>
                alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
                window.location.href = 'index.php';
              </script>";
        exit();
    }
}
?>
