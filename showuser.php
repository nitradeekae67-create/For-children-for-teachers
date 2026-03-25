<?php
require 'auth.php';
checkRole(['admin']);

$servername = "localhost"; $username = "root"; $password = ""; $dbname = "project";
$conn = new mysqli($servername, $username, $password, $dbname); 
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// --- ดึงข้อมูลสถิติ (เก็บของเดิมไว้ และเพิ่มของใหม่) ---
$count_admin     = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetch_row()[0] ?? 0;
$count_donor     = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' OR role = 'donor'")->fetch_row()[0] ?? 0;

// สถิติจิตอาสาแบบเจาะจง
$count_v_all     = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'volunteer'")->fetch_row()[0] ?? 0;
$count_v_active  = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'volunteer' AND status = 'active'")->fetch_row()[0] ?? 0;
$count_v_pending = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'volunteer' AND (status != 'active' OR status IS NULL)")->fetch_row()[0] ?? 0;

$sql    = "SELECT id, username, email, first_name, last_name, phone, province, address, role, status, created_at FROM users ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก | ระบบปันสุข</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Sarabun:wght@400;600;700&display=swap');
        body { font-family: 'IBM Plex Sans Thai', sans-serif; background-color: #f0fdf4; margin: 0; }
        .admin-main-content { margin-left: 260px; padding: 40px; }
        .content-card { background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        #userModal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; padding: 0; border-radius: 24px; max-width: 550px; width: 90%; position: relative; animation: zoomIn 0.2s ease-out; overflow: hidden; }
        @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .clickable-row:hover { cursor: pointer; background-color: #f0fdf4 !important; }
        @media (max-width: 1024px) { .admin-main-content { margin-left: 0; padding: 20px; } }
        
        /* สไตล์พิเศษสำหรับการ์ดแจ้งเตือน */
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<?php include 'menu_admin.php'; ?>

<div id="userModal">
    <div class="modal-content shadow-2xl">
        <div class="bg-emerald-600 p-6 text-white flex justify-between items-center">
            <h3 class="text-xl font-bold"><i class="fas fa-user-circle mr-2"></i>รายละเอียดสมาชิก</h3>
            <button onclick="closeModal()" class="text-white/80 hover:text-white"><i class="fas fa-times text-2xl"></i></button>
        </div>
        <div id="modalBody" class="p-8"></div>
        <div class="bg-slate-50 p-4 flex justify-end">
            <button onclick="closeModal()" class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-xl font-bold hover:bg-slate-100 transition">ปิดหน้าต่าง</button>
        </div>
    </div>
</div>

<main class="admin-main-content">
    <div class="max-w-7xl mx-auto">

        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800" style="font-family:'Sarabun';">👥 จัดการสมาชิก</h1>
                <p class="text-slate-500 mt-1">ยืนยันสิทธิ์เฉพาะตำแหน่งจิตอาสา และจัดการข้อมูลสมาชิกในระบบ</p>
            </div>
            <a href="add_user.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg transition-all">
                <i class="fas fa-plus-circle mr-2"></i>เพิ่มสมาชิกใหม่
            </a>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            
            <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-emerald-500 stat-card">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ผู้ดูแลระบบ</p>
                <p class="text-xl font-black text-slate-700"><?php echo number_format($count_admin); ?> ท่าน</p>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-slate-400 stat-card">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ผู้บริจาคทั่วไป</p>
                <p class="text-xl font-black text-slate-700"><?php echo number_format($count_donor); ?> ท่าน</p>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-blue-500 stat-card">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">จิตอาสาทั้งหมด</p>
                <p class="text-xl font-black text-blue-600"><?php echo number_format($count_v_all); ?> ท่าน</p>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-emerald-400 stat-card">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">จิตอาสา (ยืนยันแล้ว)</p>
                <p class="text-xl font-black text-emerald-600"><?php echo number_format($count_v_active); ?> ท่าน</p>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-amber-400 stat-card relative overflow-hidden">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">จิตอาสา (รอยืนยัน)</p>
                <p class="text-xl font-black <?php echo $count_v_pending > 0 ? 'text-amber-500' : 'text-slate-700'; ?>">
                    <?php echo number_format($count_v_pending); ?> ท่าน
                </p>
                <?php if($count_v_pending > 0): ?>
                    <span class="absolute top-1 right-1 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                    </span>
                <?php endif; ?>
            </div>

        </div>

        <div class="content-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 text-sm font-bold">
                        <tr>
                            <th class="px-6 py-4">ชื่อ-นามสกุล</th>
                            <th>การติดต่อ</th>
                            <th class="text-center">ประเภท</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">ยืนยันสิทธิ์</th>
                            <th class="text-center px-6">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                    <?php while ($row = $result->fetch_assoc()):
                        $userData = json_encode($row, JSON_UNESCAPED_UNICODE);

                        // การแสดงผล Role
                        $roleLabel = match($row['role']) {
                            'admin'     => 'ผู้ดูแล',
                            'volunteer' => 'จิตอาสา',
                            default     => 'ผู้บริจาค',
                        };
                        $roleColor = match($row['role']) {
                            'admin'     => 'bg-green-100 text-green-700',
                            'volunteer' => 'bg-blue-100 text-blue-700',
                            default     => 'bg-slate-100 text-slate-600',
                        };

                        // การแสดงผล Status
                        $isVolunteer = ($row['role'] === 'volunteer');
                        $isActive    = ($row['status'] === 'active');
                        $isPending   = $isVolunteer && !$isActive;

                        if ($isPending) {
                            $statusText  = 'รอการยืนยัน';
                            $statusStyle = 'bg-amber-50 text-amber-600';
                        } else {
                            $statusText  = 'ใช้งานได้';
                            $statusStyle = 'bg-emerald-50 text-emerald-600';
                        }
                    ?>
                    <tr class="clickable-row transition-colors group">
                        <td class="px-6 py-4" onclick='showUserDetail(<?php echo $userData; ?>)'>
                            <div class="font-bold text-slate-800 group-hover:text-emerald-700">
                                <?php echo htmlspecialchars($row["first_name"] . " " . $row["last_name"]); ?>
                            </div>
                            <div class="text-xs text-slate-400">@<?php echo htmlspecialchars($row["username"]); ?></div>
                        </td>
                        
                        <td class="text-xs text-slate-600 py-4" onclick='showUserDetail(<?php echo $userData; ?>)'>
                            <div><i class="far fa-envelope mr-1 text-emerald-500"></i><?php echo htmlspecialchars($row["email"]); ?></div>
                            <div><i class="fas fa-phone-alt mr-1 text-emerald-500"></i><?php echo htmlspecialchars($row["phone"]); ?></div>
                        </td>

                        <td class="py-4 text-center" onclick='showUserDetail(<?php echo $userData; ?>)'>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold <?php echo $roleColor; ?>">
                                <?php echo $roleLabel; ?>
                            </span>
                        </td>

                        <td class="py-4 text-center">
                            <span class="px-2 py-1 rounded-lg text-[10px] font-bold <?php echo $statusStyle; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </td>

                        <td class="py-4 text-center">
                            <?php if ($isPending): ?>
                                <button onclick="approveVolunteer(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['first_name']); ?>')"
                                        class="bg-amber-500 hover:bg-amber-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold transition-all shadow-sm whitespace-nowrap">
                                    <i class="fas fa-user-check mr-1"></i>ยืนยัน
                                </button>
                            <?php else: ?>
                                <span class="text-slate-300 text-sm">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="edit_user.php?id=<?php echo $row['id']; ?>"
                                   class="p-2 text-amber-500 hover:bg-amber-50 rounded-lg transition-colors">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button"
                                        onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']); ?>')"
                                        class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    // ยืนยันการอนุมัติจิตอาสา
    function approveVolunteer(userId, name) {
        Swal.fire({
            title: 'ยืนยันตัวตนจิตอาสา?',
            text: `คุณต้องการอนุมัติให้คุณ ${name} เริ่มใช้งานระบบได้ใช่หรือไม่?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'ยืนยันการอนุมัติ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `approve_process.php?id=${userId}`;
            }
        });
    }

    // ยืนยันการลบ
    function confirmDelete(userId, userName) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: `คุณต้องการลบข้อมูลคุณ ${userName} หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ยืนยันการลบ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete_user.php?id=${userId}`;
            }
        });
    }

    // แสดง Modal รายละเอียด
    function showUserDetail(user) {
        const modal = document.getElementById('userModal');
        const body  = document.getElementById('modalBody');
        const roleName = user.role === 'admin' ? 'ผู้ดูแลระบบ' : (user.role === 'volunteer' ? 'จิตอาสา' : 'ผู้บริจาค');
        const statusLabel = (user.role === 'volunteer' && user.status !== 'active') ? 'รอการยืนยัน' : 'ใช้งานได้';

        body.innerHTML = `
            <div class="grid grid-cols-2 gap-6">
                <div class="col-span-2 flex items-center gap-4 bg-slate-50 p-4 rounded-2xl mb-2">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-2xl font-bold">
                        ${user.first_name.charAt(0)}
                    </div>
                    <div>
                        <div class="text-xl font-bold text-slate-800">${user.first_name} ${user.last_name}</div>
                        <div class="text-sm text-slate-500">Role: ${roleName} | สถานะ: ${statusLabel}</div>
                    </div>
                </div>
                <div><label class="text-xs font-bold text-slate-400">อีเมล</label><div class="text-slate-700 font-medium">${user.email}</div></div>
                <div><label class="text-xs font-bold text-slate-400">เบอร์โทรศัพท์</label><div class="text-slate-700 font-medium">${user.phone || '-'}</div></div>
                <div class="col-span-2 border-t pt-4">
                    <label class="text-xs font-bold text-slate-400">ที่อยู่ตามลงทะเบียน</label>
                    <div class="text-slate-700 mt-1 font-medium">${user.address || '-'} จ.${user.province || '-'}</div>
                </div>
                <div class="col-span-2">
                    <label class="text-xs font-bold text-slate-400">วันที่ลงทะเบียน</label>
                    <div class="text-slate-500 text-sm">${user.created_at || '-'}</div>
                </div>
            </div>`;
        modal.style.display = 'flex';
    }

    // ปิด Modal
    function closeModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    // ปิด Modal เมื่อคลิกพื้นหลัง
    window.onclick = function(event) {
        const modal = document.getElementById('userModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

</body>
</html>