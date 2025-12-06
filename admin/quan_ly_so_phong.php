<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit; 
}

include '../includes/ketnoidb.php';
include '../includes/headeradmin.php';

// ... (Phần xử lý Xóa giữ nguyên) ...
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $check = $ketNoiDb->query("SELECT trang_thai, so_phong FROM phong WHERE id = $id")->fetch_assoc();
    if ($check && $check['trang_thai'] == 'Đang ở') {
        echo "<script>alert('Lỗi: Phòng " . $check['so_phong'] . " đang có khách, không thể xóa!'); window.location.href='quan_ly_so_phong.php';</script>";
    } else {
        try {
            $ketNoiDb->query("DELETE FROM phong WHERE id = $id");
            echo "<script>alert('Đã xóa phòng thành công!'); window.location.href='quan_ly_so_phong.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Không thể xóa do ràng buộc dữ liệu cũ.'); window.location.href='quan_ly_so_phong.php';</script>";
        }
    }
}

$sql = "SELECT p.id, p.so_phong, p.tang, p.trang_thai, lp.ten_loai 
        FROM phong p 
        JOIN loai_phong lp ON p.loai_phong_id = lp.id 
        ORDER BY p.tang ASC, p.so_phong ASC";
$result = $ketNoiDb->query($sql);
?>

<main class="container page-padding">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1 class="tieu-de-muc" style="text-align:left; margin:0; color:#333;">Quản lý Số phòng</h1>
        <a href="them_so_phong.php" class="btn-big-cta" style="padding:10px 20px; font-size:1rem; background:#27ae60; border:none; color:white; text-decoration:none; border-radius:4px;">
            <i class="fas fa-plus"></i> Thêm phòng mới
        </a>
    </div>

    <div class="table-card" style="background:white; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
        <table class="modern-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background-color: #f1f1f1; color: #333;">
                    <th style="padding:15px; border-bottom:2px solid #ddd; text-align:left;">Số phòng</th>
                    <th style="padding:15px; border-bottom:2px solid #ddd; text-align:left;">Loại phòng</th>
                    <th style="padding:15px; border-bottom:2px solid #ddd; text-align:center;">Tầng</th>
                    <th style="padding:15px; border-bottom:2px solid #ddd; text-align:center;">Trạng thái</th>
                    <th style="padding:15px; border-bottom:2px solid #ddd; text-align:center;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        
                        <td style="padding:15px; font-weight:bold; font-size:1.1rem; color:#2c3e50;">
                            <?php echo $row['so_phong']; ?>
                        </td>
                        
                        <td style="padding:15px; color:#555;">
                            <?php echo $row['ten_loai']; ?>
                        </td>
                        
                        <td style="padding:15px; text-align:center; color:#555;">
                            <?php echo $row['tang']; ?>
                        </td>
                        
                        <td style="padding:15px; text-align:center;">
    <?php 
        $bg = "#95a5a6"; 
        $txt = "Trạng thái lạ"; // Nếu không khớp cái nào thì hiện cái này
        
        if($row['trang_thai'] == 'Sẵn sàng') { 
            $bg = "#2ecc71"; // Xanh lá
            $txt = "Sẵn sàng";
        }
        
        // --- THÊM ĐOẠN NÀY ---
        if($row['trang_thai'] == 'Đã đặt') { 
            $bg = "#3498db"; // Xanh dương (Blue)
            $txt = "Đã đặt";
        }
        // ---------------------

        if($row['trang_thai'] == 'Đang ở') { 
            $bg = "#f39c12"; // Cam/Vàng
            $txt = "Đang ở";
        }
        if($row['trang_thai'] == 'Đang dọn') { 
            $bg = "#9b59b6"; // Tím
            $txt = "Đang dọn";
        }
        if($row['trang_thai'] == 'Bảo trì') { 
            $bg = "#e74c3c"; // Đỏ
            $txt = "Bảo trì";
        }
    ?>
    <span style="background-color: <?php echo $bg; ?>; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: 500; display: inline-block; min-width: 100px;">
        <?php echo $txt; ?>
    </span>
</td>
                        
                        <td style="padding:15px; text-align:center;">
                            <div style="display:flex; justify-content:center; gap:8px;">
                                <a href="them_so_phong.php?id=<?php echo $row['id']; ?>" 
                                   title="Sửa"
                                   style="background:#f39c12; color:white; width:35px; height:35px; line-height:35px; border-radius:4px; text-align:center; display:inline-block;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($row['trang_thai'] == 'Đang ở'): ?>
                                    <span title="Đang có khách, không thể xóa"
                                          style="background:#e0e0e0; color:#999; width:35px; height:35px; line-height:35px; border-radius:4px; text-align:center; display:inline-block; cursor:not-allowed;">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                <?php else: ?>
                                    <a href="quan_ly_so_phong.php?xoa=<?php echo $row['id']; ?>" 
                                       title="Xóa"
                                       style="background:#e74c3c; color:white; width:35px; height:35px; line-height:35px; border-radius:4px; text-align:center; display:inline-block;"
                                       onclick="return confirm('Bạn có chắc muốn xóa phòng <?php echo $row['so_phong']; ?>?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>

                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px; color:#999;">Chưa có dữ liệu phòng.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footeradmin.php'; ?>