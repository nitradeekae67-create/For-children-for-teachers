<?php
// ฟังก์ชันสำหรับดึงกิจกรรมที่ "เปิดรับบริจาค" (Active) เท่านั้น
function getActiveEvents($conn) {
    // กฎเหล็ก: ดึงเฉพาะ status = 'Active'
    $sql = "SELECT * FROM events WHERE status = 'Active' ORDER BY event_date ASC";
    $result = $conn->query($sql);
    
    $events = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    return $events; // คืนค่าเป็น Array ของกิจกรรมที่เปิดอยู่
}

// ฟังก์ชันเช็กสถานะก่อนบันทึกข้อมูล (ใช้ดักหน้า Donate หรือ Register)
function isEventOpen($conn, $event_id) {
    $stmt = $conn->prepare("SELECT status FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return ($row['status'] === 'Active'); // คืนค่า true ถ้าเปิด, false ถ้าปิด
    }
    return false;
}
?>