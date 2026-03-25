<?php
session_start();
require 'auth.php';
// ตรวจสอบสิทธิ์ว่าเป็น Admin หรือไม่
checkRole(['admin']);

// เชื่อมต่อฐานข้อมูล
$servername = "localhost"; $username = "root"; $password = ""; $dbname = "project";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Anuphan', sans-serif; }</style>
</head>
<body>

<?php
if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // 1. อัปเดตสถานะเป็น 'active' สำหรับ ID ที่ส่งมา
    // เราล็อคเงื่อนไขเพิ่มว่าต้องเป็น role 'volunteer' เท่านั้นเพื่อความปลอดภัย
    $sql = "UPDATE users SET status = 'active' WHERE id = ? AND role = 'volunteer'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // อนุมัติสำเร็จ
            echo "<script>
                Swal.fire({
                    title: 'อนุมัติสำเร็จ!',
                    text: 'สิทธิ์จิตอาสาถูกเปิดใช้งานเรียบร้อยแล้ว',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = 'showuser.php';
                });
            </script>";
        } else {
            // ไม่มีการเปลี่ยนแปลง (อาจเพราะเป็น active อยู่แล้ว หรือไม่ใช่ role volunteer)
            echo "<script>
                Swal.fire({
                    title: 'ไม่พบการเปลี่ยนแปลง',
                    text: 'ผู้ใช้รายนี้อาจได้รับการอนุมัติไปแล้ว หรือข้อมูลไม่ถูกต้อง',
                    icon: 'info',
                    confirmButtonText: 'กลับไปหน้าจัดการ'
                }).then(() => {
                    window.location.href = 'showuser.php';
                });
            </script>";
        }
    } else {
        // เกิดข้อผิดพลาดในคำสั่ง SQL
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถอัปเดตข้อมูลได้ในขณะนี้',
                icon: 'error',
                confirmButtonText: 'ลองใหม่'
            }).then(() => {
                window.location.href = 'showuser.php';
            });
        </script>";
    }
    $stmt->close();
} else {
    // กรณีไม่มีการส่ง ID มา
    header("Location: showuser.php");
    exit();
}

$conn->close();
?>
</body>
</html>