<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $username = $_POST['username'];
    $password = $_POST['password']; 
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $address = $_POST['address'];
    $province = $_POST['province'];

    // เข้ารหัสผ่านเพื่อความปลอดภัย
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ตรวจสอบว่า Username ซ้ำหรือไม่ (ใช้ Prepared Statement)
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo "<script>
            alert('ชื่อผู้ใช้นี้มีในระบบแล้ว!');
            window.history.back();
        </script>";
    } else {
        // คำสั่ง SQL ใช้ Prepared Statement
        $stmt = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, phone, role, address, province) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $username, $hashed_password, $first_name, $last_name, $email, $phone, $role, $address, $province);

        if ($stmt->execute()) {
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'showuser.php';
                }, 500);
            </script>";
        } else {
            echo "Error: " . $conn->error;
        }
        $stmt->close();
    }
    $stmt_check->close();
}
mysqli_close($conn);
?>