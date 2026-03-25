<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

$conn = new mysqli("localhost","root","","project");
$conn->set_charset("utf8mb4");

if(!isset($_GET['id']) || empty($_GET['id'])){
    die("ไม่พบ ID กิจกรรม");
}

$id = intval($_GET['id']);

/* ดึงข้อมูลกิจกรรม */

$sql = "SELECT * FROM events WHERE event_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("ไม่พบกิจกรรมในฐานข้อมูล");
}

$event = $result->fetch_assoc();

/* ถ้ากิจกรรมปิด */

$status = strtolower(trim($event['status']));

if($status != 'active' && $status != 'inactive'){
    die("ไม่สามารถลบกิจกรรมนี้ได้");
}

$conn->begin_transaction();

try{

/* ลบคลังบริจาค */

$sql1="DELETE FROM event_item_targets WHERE event_id=?";
$stmt=$conn->prepare($sql1);
$stmt->bind_param("i",$id);
$stmt->execute();

/* ลบกิจกรรม */

$sql2="DELETE FROM events WHERE event_id=?";
$stmt=$conn->prepare($sql2);
$stmt->bind_param("i",$id);
$stmt->execute();

/* ลบรูป */

if(!empty($event['event_image'])){

$file="img/".$event['event_image'];

if(file_exists($file)){
unlink($file);
}

}

$conn->commit();

header("Location: manage_events.php");
exit();

}catch(Exception $e){

$conn->rollback();
echo "เกิดข้อผิดพลาดในการลบ";

}

?>