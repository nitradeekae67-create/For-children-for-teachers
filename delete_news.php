<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

// เช็คว่ามี id ส่งมาหรือไม่
if(isset($_GET['id'])){

    $news_id = intval($_GET['id']);

    // 1️⃣ ดึงรูปภาพทั้งหมดก่อนลบ
    $sql_img = "SELECT image_path FROM news_images WHERE news_id = ?";
    $stmt_img = $conn->prepare($sql_img);
    $stmt_img->bind_param("i", $news_id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();

    if($result_img){
        while($row = $result_img->fetch_assoc()){
            $file = "img/".$row['image_path'];
            if(!empty($row['image_path']) && file_exists($file)){
                unlink($file); // ลบไฟล์จริง
            }
        }
    }
    $stmt_img->close();

    // 2️⃣ ลบรูปในฐานข้อมูล
    $sql_del_img = "DELETE FROM news_images WHERE news_id = ?";
    $stmt_del_img = $conn->prepare($sql_del_img);
    $stmt_del_img->bind_param("i", $news_id);
    $stmt_del_img->execute();
    $stmt_del_img->close();

    // 3️⃣ ลบข่าว
    $sql_del_news = "DELETE FROM news_update WHERE news_id = ?";
    $stmt_del_news = $conn->prepare($sql_del_news);
    $stmt_del_news->bind_param("i", $news_id);

    if($stmt_del_news->execute()){
        echo "<script>
                alert('ลบข่าวเรียบร้อยแล้ว');
                window.location='manage_news.php';
              </script>";
    }else{
        echo "Error: ".$conn->error;
    }
    $stmt_del_news->close();

}else{
    header("Location: manage_news.php");
    exit();
}
?>