<?php
include('connect.php');
ini_set('display_errors', 1); error_reporting(E_ALL);
$conn = new mysqli("localhost", "root", "", "project");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
?>

<style>
    :root {
        --primary-navy: #1c355e;
        --accent-blue: #3b82f6;
        --soft-blue: #eff6ff;
        --glass-white: rgba(255, 255, 255, 0.9);
        --navy-grad: linear-gradient(135deg, #1c355e 0%, #3b82f6 100%);
        --success-green: #10b981;
        --warning-orange: #f59e0b;
    }

    .item-tile { 
        background: var(--glass-white); 
        backdrop-filter: blur(10px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.6) !important;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05) !important;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .item-tile:hover { 
        transform: translateY(-10px); 
        box-shadow: 0 20px 40px -15px rgba(28, 53, 94, 0.15) !important;
    }

    .category-badge {
        background: var(--soft-blue);
        color: var(--accent-blue);
        border: 1px solid #dbeafe;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 10px;
    }

    .stock-card {
        background: #f8fafc;
        border-radius: 18px;
        padding: 15px;
        border: 1px solid #edf2f7;
    }

    .qty-box {
        background: #f1f5f9;
        border-radius: 20px;
        padding: 8px;
        border: 1px solid #e2e8f0;
    }

    .btn-qty {
        width: 40px; height: 40px;
        background: white;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .qty-input {
        font-size: 1.4rem !important;
        font-weight: 800 !important;
        color: var(--primary-navy) !important;
    }

    .animate-fade-up {
        animation: fadeUp 0.5s ease-out forwards;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="container pb-5">
    <?php
    if ($event_id === 999): 
        /**
         * SQL Logic คลังกลาง:
         * 1. ยอดบริจาคตรง: event_id = 999 หรือ 0
         * 2. ยอดบริจาคผ่านกิจกรรมที่ยังไม่ปิด (Active/Inactive): นับทั้งหมด ไม่หัก
         * 3. ยอดส่วนเกินจากกิจกรรมที่ปิดแล้ว (Closed): นับเฉพาะ actual - target
         */
        $sql = "SELECT i.item_id, i.item_name, i.unit, i.category,

                -- 1. บริจาคตรงเข้าคลัง (event_id = 999 หรือ 0)
                IFNULL((
                    SELECT SUM(d.quantity) 
                    FROM donations d
                    WHERE d.item_id = i.item_id 
                      AND d.status = 'Approved' 
                      AND (d.event_id = 999 OR d.event_id = 0 OR d.event_id IS NULL)
                ), 0) as direct_stock,

                -- 2. บริจาคผ่านกิจกรรมที่ยังไม่ปิด (นับเต็มทุกบาท ไม่หัก)
                IFNULL((
                    SELECT SUM(d.quantity)
                    FROM donations d
                    INNER JOIN events e ON e.event_id = d.event_id
                    WHERE d.item_id = i.item_id
                      AND d.status = 'Approved'
                      AND d.event_id != 999
                      AND d.event_id != 0
                      AND e.status != 'Closed'
                ), 0) as active_stock,

                -- 3. ส่วนเกินจากกิจกรรมที่ปิดแล้ว (actual - target เฉพาะที่เกิน)
                IFNULL((
                    SELECT SUM(excess_qty)
                    FROM (
                        SELECT d.event_id, d.item_id,
                               GREATEST(
                                   SUM(d.quantity) - IFNULL((
                                       SELECT eit.target_quantity 
                                       FROM event_item_targets eit 
                                       WHERE eit.event_id = d.event_id 
                                         AND eit.item_id = d.item_id
                                   ), 0),
                               0) as excess_qty
                        FROM donations d
                        INNER JOIN events e ON e.event_id = d.event_id
                        WHERE d.status = 'Approved' 
                          AND d.event_id != 999 
                          AND d.event_id != 0
                          AND e.status = 'Closed'
                        GROUP BY d.event_id, d.item_id
                    ) as subquery 
                    WHERE subquery.item_id = i.item_id
                ), 0) as surplus_stock

                FROM donation_items i 
                WHERE i.is_active = 1
                ORDER BY FIELD(i.item_id, 8, 1, 2, 3, 4, 5, 6, 7)";

        $result = $conn->query($sql);
        
        if ($result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($row = $result->fetch_assoc()): 
                    $total_stock = $row['direct_stock'] + $row['active_stock'] + $row['surplus_stock'];
                ?>
                <div class="col-md-6 col-xl-4 animate-fade-up">
                    <div class="item-tile p-4">
                        <span class="category-badge mb-2 d-inline-block"><?= htmlspecialchars($row['category']) ?></span>
                        <h5 class="fw-700 mb-3 text-dark"><?= htmlspecialchars($row['item_name']) ?></h5>
                        
                        <div class="stock-card text-center mb-4">
                            <span class="d-block small text-muted mb-1">จำนวนของบริจาคที่มีในคลัง</span>
                            <h3 class="fw-800 mb-0 text-primary">
                                <?= number_format($total_stock) ?> <small class="fs-6 fw-normal text-muted"><?= $row['unit'] ?></small>
                            </h3>
                            <hr class="my-2 opacity-50">
                        </div>

                        <div class="qty-box d-flex align-items-center justify-content-between mt-auto">
                            <button type="button" class="btn-qty minus"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" name="items[<?= $row['item_id'] ?>]" class="form-control border-0 bg-transparent text-center qty-input" value="0" min="0">
                            <button type="button" class="btn-qty plus"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; 

    elseif ($event_id > 0): 
        $sql = "SELECT i.item_name, i.unit, i.category, t.target_quantity, t.item_id,
                    (SELECT SUM(d.quantity) 
                     FROM donations d
                     WHERE d.item_id = t.item_id 
                       AND d.event_id = t.event_id 
                       AND d.status = 'Approved'
                    ) as current_received
                FROM event_item_targets AS t
                INNER JOIN donation_items AS i ON t.item_id = i.item_id
                WHERE t.event_id = ?
                ORDER BY FIELD(i.item_id, 8, 1, 2, 3, 4, 5, 6, 7)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($row = $result->fetch_assoc()): 
                    $percent = ($row['target_quantity'] > 0) ? ($row['current_received'] / $row['target_quantity']) * 100 : 0;
                    $percent = min(100, round($percent));
                ?>
                <div class="col-md-6 col-xl-4 animate-fade-up">
                    <div class="item-tile p-4">
                        <span class="category-badge mb-2 d-inline-block"><?= htmlspecialchars($row['category']) ?></span>
                        <h5 class="fw-700 mb-3 text-dark"><?= htmlspecialchars($row['item_name']) ?></h5>
                        <div class="progress mb-3"><div class="progress-bar" style="width: <?= $percent ?>%"></div></div>
                        <div class="row g-0 mb-4 py-2 border-top border-bottom border-light text-center">
                            <div class="col-6 border-end">
                                <span class="d-block small text-muted">จำนวนที่ต้องการ</span>
                                <b><?= number_format($row['target_quantity']) ?></b>
                            </div>
                            <div class="col-6">
                                <span class="d-block small text-muted">ได้รับแล้ว</span>
                                <b class="text-primary"><?= number_format($row['current_received']) ?></b>
                            </div>
                        </div>
                        <div class="qty-box d-flex align-items-center justify-content-between mt-auto">
                            <button type="button" class="btn-qty minus"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" name="items[<?= $row['item_id'] ?>]" class="form-control border-0 bg-transparent text-center qty-input" value="0" min="0">
                            <button type="button" class="btn-qty plus"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; 
    endif; ?>
</div>