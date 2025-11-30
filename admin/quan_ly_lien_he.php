<?php
session_start();

// 1. BẢO MẬT
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../includes/ketnoidb.php';
include '../includes/headeradmin.php';

// 2. XỬ LÝ HÀNH ĐỘNG (Xóa & Đánh dấu đã xem)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] == 'xoa') {
        $ketNoiDb->query("DELETE FROM lien_he WHERE id = $id");
    } 
    elseif ($_GET['action'] == 'da_xem') {
        $ketNoiDb->query("UPDATE lien_he SET trang_thai = 'Da_xem' WHERE id = $id");
    }
    
    echo "<script>window.location.href='quan_ly_lien_he.php';</script>";
}

// 3. TÍNH TOÁN SỐ LIỆU
$sqlTotal = "SELECT COUNT(*) as total FROM lien_he";
$totalMsg = $ketNoiDb->query($sqlTotal)->fetch_assoc()['total'];

$sqlUnread = "SELECT COUNT(*) as unread FROM lien_he WHERE trang_thai = 'Chua_xem'";
$unreadMsg = $ketNoiDb->query($sqlUnread)->fetch_assoc()['unread'];

// 4. LẤY DANH SÁCH TIN NHẮN
$sql = "SELECT * FROM lien_he ORDER BY ngay_gui DESC";
$result = $ketNoiDb->query($sql);
?>

<main class="container page-padding">
    
    <div class="dashboard-stats" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $totalMsg; ?></h3>
                <p>Tổng tin nhắn</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fef2f2; color:#dc2626;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $unreadMsg; ?></h3>
                <p>Tin chưa đọc</p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-envelope"></i> Hộp thư khách hàng</div>
            <a href="quan_ly_lien_he.php" style="color:#666;"><i class="fas fa-sync-alt"></i> Làm mới</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="20%">Người gửi</th>
                        <th width="45%">Nội dung</th>
                        <th width="15%">Ngày gửi</th>
                        <th width="10%">Trạng thái</th>
                        <th width="5%" style="text-align:center;">Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr style="<?php echo ($row['trang_thai'] == 'Chua_xem') ? 'background-color:#fffbeb;' : ''; ?>">
                                <td>#<?php echo $row['id']; ?></td>
                                
                                <td>
                                    <span class="customer-name"><?php echo htmlspecialchars($row['ho_ten']); ?></span>
                                    <span class="customer-phone" style="font-size:0.8rem; color:#666;">
                                        <?php echo htmlspecialchars($row['email']); ?>
                                    </span>
                                </td>

                                <td>
                                    <div style="max-height: 60px; overflow-y: auto; font-size: 0.95rem; color: #444;">
                                        <?php echo nl2br(htmlspecialchars($row['noi_dung'])); ?>
                                    </div>
                                </td>

                                <td style="color:#777; font-size:0.85rem;">
                                    <?php echo date('H:i d/m/Y', strtotime($row['ngay_gui'])); ?>
                                </td>

                                <td>
                                    <?php if($row['trang_thai'] == 'Chua_xem'): ?>
                                        <a href="quan_ly_lien_he.php?action=da_xem&id=<?php echo $row['id']; ?>" class="badge badge-warning" style="text-decoration:none; cursor:pointer;" title="Bấm để đánh dấu đã đọc">
                                            <i class="fas fa-eye"></i> Mới
                                        </a>
                                    <?php else: ?>
                                        <span class="badge badge-success" style="background:#f3f4f6; color:#6b7280;">Đã xem</span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align:center;">
                                    <a href="quan_ly_lien_he.php?action=xoa&id=<?php echo $row['id']; ?>" class="action-btn btn-red" title="Xóa tin nhắn" onclick="return confirm('Bạn có chắc muốn xóa tin nhắn này?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                <i class="fas fa-comment-slash" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                Hộp thư trống.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footeradmin.php'; ?>