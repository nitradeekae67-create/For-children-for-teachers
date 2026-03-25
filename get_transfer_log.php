<?php
require 'auth.php';
checkRole(['admin']);
include('connect.php');

$conn->set_charset("utf8mb4");

header('Content-Type: application/json');

$transfers = [];

$res = $conn->query("
    SELECT 
        d.donation_id,
        d.donation_date,
        e.event_name,
        i.sub_category,
        d.quantity,
        e.status AS event_status
    FROM donations d
    JOIN events e         ON d.event_id = e.event_id
    JOIN donation_items i ON d.item_id  = i.item_id
    WHERE d.user_id = 0
      AND d.status = 'Approved'
    ORDER BY d.donation_date DESC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $transfers[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $transfers]);
?>
