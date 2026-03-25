<?php
require 'auth.php';
checkRole(['admin']);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection Error");

if (!isset($_GET['id'])) die("ไม่พบรหัสผู้ใช้");
$user_id = $_GET['id'];

// 1. ดึงข้อมูลเดิมมาโชว์ (ดึงแค่ที่จำเป็น)
$stmt = $conn->prepare("SELECT first_name, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $username, $role);
$stmt->fetch();
$stmt->close();

// 2. ส่วนการบันทึก (แก้เฉพาะ Role ตามหลัก Admin Management)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si", $new_role, $user_id);

    if ($stmt->execute()) {
        header("Location: showuser.php?edit=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสิทธิ์ผู้ใช้งาน</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #064e3b; text-align: center; margin-bottom: 25px; }
        .input-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; background: #fff; font-size: 16px; }
        input[readonly] { background: #eee; cursor: not-allowed; }
        .btn-save { width: 100%; padding: 15px; border: none; border-radius: 10px; background: #10b981; color: white; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-save:hover { background: #059669; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="box">
    <h2>จัดการสิทธิ์ผู้ใช้งาน</h2>
    
    <form method="POST">
        <div class="input-group">
            <label>ชื่อจริง (อ่านอย่างเดียว)</label>
            <input type="text" value="<?php echo htmlspecialchars($first_name); ?>" readonly>
        </div>

        <div class="input-group">
            <label>ชื่อผู้ใช้ (อ่านอย่างเดียว)</label>
            <input type="text" value="<?php echo htmlspecialchars($username); ?>" readonly>
        </div>

        <div class="input-group">
            <label>ตำแหน่งสิทธิ์การใช้งาน</label>
            <select name="role">
                <option value="admin" <?php if($role=="admin") echo "selected"; ?>>ผู้ดูแลระบบ</option>
                <option value="volunteer" <?php if ($role=="volunteer") echo "selected"; ?>>จิตอาสา</option>
                <option value="user" <?php if($role=="user") echo "selected"; ?>>ผู้บริจาค</option>
            </select>
        </div>

        <button type="submit" class="btn-save">บันทึกการเปลี่ยนสิทธิ์</button>
        <a href="showuser.php" class="back-link">ยกเลิกและกลับไปหน้าเดิม</a>
    </form>
</div>

</body>
</html>