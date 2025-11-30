<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../includes/ketnoidb.php';
include '../includes/headeradmin.php';

// --- PHẦN 1: XỬ LÝ KHI BẤM NÚT XÓA ---
if (isset($_GET['action']) && $_GET['action'] == 'xoa' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // BƯỚC 1: Kiểm tra xem có phòng nào thuộc loại này đang có khách không?
    // (Logic an toàn phía Server: Dù hack disable nút bấm vẫn không xóa được)
    $sqlCheckBusy = "SELECT COUNT(*) as cnt FROM phong WHERE loai_phong_id = $id AND trang_thai = 'Đang ở'";
    $checkBusy = $ketNoiDb->query($sqlCheckBusy)->fetch_assoc();
    
    if ($checkBusy['cnt'] > 0) {
        // Nếu có khách -> Chặn ngay
        echo "<script>
                alert('CẢNH BÁO: Đang có khách ở trong loại phòng này! \\nKhông thể xóa hoặc sửa vào lúc này.'); 
                window.location.href='quan_ly_phong.php';
              </script>";
        exit;
    } 
    
    // BƯỚC 2: Kiểm tra xem còn phòng trống nào thuộc loại này không?
    // (Yêu cầu xóa hết phòng con trước khi xóa loại cha)
    $sqlCheckAll = "SELECT COUNT(*) as cnt FROM phong WHERE loai_phong_id = $id";
    $checkAll = $ketNoiDb->query($sqlCheckAll)->fetch_assoc();

    if ($checkAll['cnt'] > 0) {
        echo "<script>
                alert('Lỗi ràng buộc dữ liệu: \\nBạn phải xóa hết các phòng số (101, 102...) thuộc loại này bên mục \"Quản lý số phòng\" trước.'); 
                window.location.href='quan_ly_phong.php';
              </script>";
    } else {
        // BƯỚC 3: Nếu sạch sẽ hoàn toàn -> Cho phép xóa
        $sqlDelete = "DELETE FROM loai_phong WHERE id = $id";
        if ($ketNoiDb->query($sqlDelete)) {
            echo "<script>
                    alert('Đã xóa loại phòng thành công!'); 
                    window.location.href='quan_ly_phong.php';
                  </script>";
        } else {
            echo "<script>alert('Lỗi Database: " . $ketNoiDb->error . "');</script>";
        }
    }
}

// --- PHẦN 2: LẤY DANH SÁCH & ĐẾM TRẠNG THÁI ---
$sql = "SELECT lp.*, 
        (SELECT COUNT(*) FROM phong p WHERE p.loai_phong_id = lp.id) as tong_so_phong,
        (SELECT COUNT(*) FROM phong p WHERE p.loai_phong_id = lp.id AND p.trang_thai = 'Đang ở') as dang_o,
        (SELECT COUNT(*) FROM phong p WHERE p.loai_phong_id = lp.id AND p.trang_thai = 'Sẵn sàng') as san_sang
        FROM loai_phong lp 
        ORDER BY lp.id ASC";
$result = $ketNoiDb->query($sql);
?>

<main class="container page-padding">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 class="tieu-de-muc" style="text-align: left; margin: 0;">Quản lý Loại Phòng</h1>
        <a href="them_sua_phong.php" class="btn-big-cta" style="padding: 10px 20px; font-size: 1rem; background: #27ae60; border: none; color: white; text-decoration: none; border-radius: 5px;">
            <i class="fas fa-plus"></i> Thêm loại phòng mới
        </a>
    </div>

    <div class="table-card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-bed"></i> Danh sách các loại phòng</div>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="10%">Hình ảnh</th>
                        <th width="20%">Tên phòng</th>
                        <th width="15%">Giá / Đêm</th>
                        <th width="20%">Thông tin</th>
                        <th width="10%">Tổng SL</th>
                        <th width="10%">Đang ở</th> 
                        <th width="10%" style="text-align:center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php
                                // Xử lý ảnh
                                if (!empty($row['anh_dai_dien'])) {
                                    $imgData = base64_encode($row['anh_dai_dien']);
                                    $src = 'data:image/jpeg;base64,' . $imgData;
                                } else { $src = '../images/no-image.jpg'; }
                                
                                // --- LOGIC QUAN TRỌNG NHẤT ---
                                // Kiểm tra xem có khách đang ở không?
                                $dangCoKhach = ($row['dang_o'] > 0); 
                            ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <img src="<?php echo $src; ?>" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                </td>
                                <td>
                                    <span class="room-name" style="font-weight:bold; font-size:1.1em;"><?php echo $row['ten_loai']; ?></span>
                                    <div style="margin-top:5px; font-size:0.85rem; color:#27ae60;">
                                        <i class="fas fa-check-circle"></i> Trống: <strong><?php echo $row['san_sang']; ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="price-tag" style="color:#e67e22; font-weight:bold;">
                                        <?php echo number_format($row['gia_tien'], 0, ',', '.'); ?>đ
                                    </span>
                                </td>
                                <td style="font-size: 0.9rem; color: #666;">
                                    <i class="fas fa-user"></i> <?php echo $row['suc_chua']; ?> | 
                                    <i class="fas fa-bed"></i> <?php echo $row['so_giuong']; ?> <br>
                                    <i class="fas fa-eye"></i> <?php echo $row['huong_nhin']; ?>
                                </td>
                                
                                <td>
                                    <span class="badge badge-success" style="background:#eef2ff; color:#4f46e5; padding: 5px 10px; border-radius: 10px;">
                                        <?php echo $row['tong_so_phong']; ?> phòng
                                    </span>
                                </td>

                                <td>
                                    <?php if($dangCoKhach): ?>
                                        <span class="badge badge-danger" style="background:#fef2f2; color:#dc2626; padding: 5px 10px; border-radius: 10px; font-weight:bold; border: 1px solid #fecaca;">
                                            <i class="fa fa-user-clock"></i> <?php echo $row['dang_o']; ?> khách
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#ccc;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align: center;">
                                    <div class="action-group" style="display: flex; gap: 5px; justify-content: center;">
                                        
                                        <?php if ($dangCoKhach): ?>
                                            
                                            <span class="action-btn" 
                                                  style="background:#eee; color:#999; cursor:not-allowed; padding: 5px 10px; border-radius: 4px;" 
                                                  title="Đang có khách ở, KHÔNG THỂ SỬA">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                            
                                            <span class="action-btn" 
                                                  style="background:#eee; color:#999; cursor:not-allowed; padding: 5px 10px; border-radius: 4px;" 
                                                  title="Đang có khách ở, KHÔNG THỂ XÓA">
                                                <i class="fas fa-trash-alt"></i>
                                            </span>

                                        <?php else: ?>
                                            
                                            <a href="them_sua_phong.php?id=<?php echo $row['id']; ?>" 
                                               class="action-btn btn-orange" 
                                               title="Sửa" 
                                               style="background:#f39c12; color:white; padding: 5px 10px; border-radius: 4px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <a href="quan_ly_phong.php?action=xoa&id=<?php echo $row['id']; ?>" 
                                               class="action-btn btn-red" 
                                               title="Xóa" 
                                               style="background:#e74c3c; color:white; padding: 5px 10px; border-radius: 4px;"
                                               onclick="return confirm('Cảnh báo: Hành động này không thể hoàn tác.\nBạn có chắc muốn xóa loại phòng này?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>

                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px;">Chưa có loại phòng nào được tạo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footeradmin.php'; ?>