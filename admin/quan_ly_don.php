<?php
session_start();

// 1. BẢO MẬT
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

include '../includes/ketnoidb.php';
include '../includes/headeradmin.php'; 

// =================================================================================
// 2. TỰ ĐỘNG QUÉT & HỦY ĐƠN QUÁ HẠN (AUTO CRON)
// =================================================================================
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Tìm đơn quá hạn và trả phòng dựa trên bảng CHI TIẾT
$sqlQuaHan = "SELECT id FROM dat_phong 
              WHERE trang_thai = 'Đã duyệt' 
              AND ngay_nhan < CURDATE()"; 

$dsQuaHan = $ketNoiDb->query($sqlQuaHan);

if ($dsQuaHan && $dsQuaHan->num_rows > 0) {
    while ($row = $dsQuaHan->fetch_assoc()) {
        $idDon = $row['id'];
        
        // Tìm các phòng đã gán cho đơn này để trả về Sẵn sàng
        $sqlChiTiet = "SELECT phong_id FROM chi_tiet_dat_phong WHERE dat_phong_id = $idDon";
        $resChiTiet = $ketNoiDb->query($sqlChiTiet);
        
        while($r = $resChiTiet->fetch_assoc()) {
            $pid = $r['phong_id'];
            $ketNoiDb->query("UPDATE phong SET trang_thai = 'Sẵn sàng' WHERE id = $pid");
        }

        // Xóa chi tiết và cập nhật đơn
        $ketNoiDb->query("DELETE FROM chi_tiet_dat_phong WHERE dat_phong_id = $idDon");
        $ketNoiDb->query("UPDATE dat_phong SET trang_thai = 'Hủy do vắng mặt' WHERE id = $idDon");
    }
}

// =================================================================================
// 3. XỬ LÝ HÀNH ĐỘNG CỦA ADMIN
// =================================================================================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id']; 
    $action = $_GET['action'];

    // Lấy thông tin đơn
    $sqlOrder = "SELECT * FROM dat_phong WHERE id = $id";
    $order = $ketNoiDb->query($sqlOrder)->fetch_assoc();
    
    if ($order) {
        $idLoaiPhong = $order['loai_phong_id']; 
        $soLuongCan = (int)$order['so_luong'];

        // --- DUYỆT ĐƠN (GIỮ CHỖ) ---
        if ($action == 'duyet_giu_cho') {
            
            // Tìm N phòng trống để gán
            $arrPhongDeGan = [];
            $sqlTim = "SELECT id FROM phong 
                       WHERE loai_phong_id = $idLoaiPhong 
                       AND trang_thai = 'Sẵn sàng' 
                       LIMIT $soLuongCan";
            $resTim = $ketNoiDb->query($sqlTim);
            
            if ($resTim->num_rows < $soLuongCan) {
                echo "<script>alert('Không đủ phòng trống để duyệt!'); window.location.href='quan_ly_don.php';</script>";
                exit;
            }

            // Bắt đầu gán phòng
            while($r = $resTim->fetch_assoc()) {
                $pid = $r['id'];
                // 1. Thêm vào bảng chi tiết
                $ketNoiDb->query("INSERT INTO chi_tiet_dat_phong (dat_phong_id, phong_id) VALUES ($id, $pid)");
                // 2. Đổi trạng thái phòng -> Đã đặt
                $ketNoiDb->query("UPDATE phong SET trang_thai = 'Đã đặt' WHERE id = $pid");
            }
            
            // 3. Update đơn
            $ketNoiDb->query("UPDATE dat_phong SET trang_thai = 'Đã duyệt' WHERE id = $id");
            echo "<script>alert('Đã duyệt và giữ chỗ thành công!'); window.location.href='quan_ly_don.php';</script>";
        } 

        // --- CHECK-IN ---
        elseif ($action == 'check_in') {
            // Lấy danh sách phòng đã giữ chỗ từ bảng chi tiết
            $sqlChiTiet = "SELECT phong_id FROM chi_tiet_dat_phong WHERE dat_phong_id = $id";
            $resChiTiet = $ketNoiDb->query($sqlChiTiet);
            
            while($r = $resChiTiet->fetch_assoc()) {
                $pid = $r['phong_id'];
                // Chuyển sang Đang ở
                $ketNoiDb->query("UPDATE phong SET trang_thai = 'Đang ở' WHERE id = $pid");
            }
            
            $ketNoiDb->query("UPDATE dat_phong SET trang_thai = 'Đang ở' WHERE id = $id"); 
            echo "<script>alert('Check-in thành công!'); window.location.href='quan_ly_don.php';</script>";
        }
        
        // --- HỦY ĐƠN ---
        elseif ($action == 'huy') {
            // Trả phòng về Sẵn sàng
            $sqlChiTiet = "SELECT phong_id FROM chi_tiet_dat_phong WHERE dat_phong_id = $id";
            $resChiTiet = $ketNoiDb->query($sqlChiTiet);
            
            while($r = $resChiTiet->fetch_assoc()) {
                $pid = $r['phong_id'];
                $ketNoiDb->query("UPDATE phong SET trang_thai = 'Sẵn sàng' WHERE id = $pid");
            }
            
            // Xóa chi tiết để dọn dẹp
            $ketNoiDb->query("DELETE FROM chi_tiet_dat_phong WHERE dat_phong_id = $id");
            
            $ketNoiDb->query("UPDATE dat_phong SET trang_thai = 'Đã hủy' WHERE id = $id");
             echo "<script>alert('Đã hủy đơn!'); window.location.href='quan_ly_don.php';</script>";
        }
        
        // --- XÓA ĐƠN ---
        elseif ($action == 'xoa') {
            // Xóa chi tiết trước (do ràng buộc khóa ngoại)
            $ketNoiDb->query("DELETE FROM chi_tiet_dat_phong WHERE dat_phong_id = $id");
            $ketNoiDb->query("DELETE FROM dat_phong WHERE id = $id");
             echo "<script>window.location.href='quan_ly_don.php';</script>";
        }
    }
}

// 4. THỐNG KÊ
$tongDon = $ketNoiDb->query("SELECT COUNT(*) as t FROM dat_phong")->fetch_assoc()['t'];
$donCho = $ketNoiDb->query("SELECT COUNT(*) as t FROM dat_phong WHERE trang_thai = 'Chờ xác nhận'")->fetch_assoc()['t'];
$donSapDen = $ketNoiDb->query("SELECT COUNT(*) as t FROM dat_phong WHERE trang_thai = 'Đã duyệt'")->fetch_assoc()['t'];

// 5. LẤY DANH SÁCH HIỂN THỊ
$sql = "SELECT dp.*, lp.ten_loai 
        FROM dat_phong dp 
        JOIN loai_phong lp ON dp.loai_phong_id = lp.id
        ORDER BY dp.ngay_dat DESC";
$result = $ketNoiDb->query($sql);
?>

<main class="container page-padding">
    
    <div class="dashboard-stats" style="display: flex; gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="flex: 1; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;">
            <div class="stat-icon" style="background: #eef2ff; color: #4f46e5; width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5em;"><i class="fas fa-file-alt"></i></div>
            <div><h3 style="margin:0;"><?php echo $tongDon; ?></h3><p style="margin:0; color:#666;">Tổng đơn</p></div>
        </div>
        <div class="stat-card" style="flex: 1; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;">
            <div class="stat-icon" style="background: #fff5e6; color: #e67e22; width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5em;"><i class="fas fa-clock"></i></div>
            <div><h3 style="margin:0;"><?php echo $donCho; ?></h3><p style="margin:0; color:#666;">Chờ xác nhận</p></div>
        </div>
        <div class="stat-card" style="flex: 1; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;">
            <div class="stat-icon" style="background: #e0f7fa; color: #006064; width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5em;"><i class="fas fa-calendar-check"></i></div>
            <div><h3 style="margin:0;"><?php echo $donSapDen; ?></h3><p style="margin:0; color:#666;">Đã giữ chỗ</p></div>
        </div>
    </div>

    <div class="table-card" style="background: white; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.05);">
        <div class="card-header" style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
            <div class="card-title" style="font-weight:bold; font-size:1.2em;"><i class="fas fa-list"></i> Quản lý Đặt phòng</div>
            <a href="quan_ly_don.php" style="color:#666; text-decoration:none;"><i class="fas fa-sync"></i> Làm mới</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 15px;">ID</th>
                        <th style="padding: 15px;">Khách hàng</th>
                        <th style="padding: 15px;">Thông tin phòng</th>
                        <th style="padding: 15px;">Ngày nhận/trả</th>
                        <th style="padding: 15px;">Tổng tiền</th> 
                        <th style="padding: 15px; text-align:center;">Trạng thái</th>
                        <th style="padding: 15px; text-align: center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php
                                // Lấy danh sách phòng đã xếp từ bảng CHI TIẾT
                                $idDon = $row['id'];
                                $sqlPhong = "SELECT p.so_phong FROM chi_tiet_dat_phong ct 
                                             JOIN phong p ON ct.phong_id = p.id 
                                             WHERE ct.dat_phong_id = $idDon";
                                $resPhong = $ketNoiDb->query($sqlPhong);
                                $dsPhong = [];
                                while($rP = $resPhong->fetch_assoc()) $dsPhong[] = $rP['so_phong'];
                            ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px;">#<?php echo $row['id']; ?></td>
                                <td style="padding: 15px;">
                                    <b><?php echo htmlspecialchars($row['ten_khach']); ?></b><br>
                                    <small><?php echo $row['sdt_khach']; ?></small>
                                </td>
                                <td style="padding: 15px;">
                                    <div style="color:#2980b9; font-weight:bold;"><?php echo $row['ten_loai']; ?></div>
                                    <div style="margin-top:5px;">
                                        <span class="badge" style="background:#eee; color:#333; padding:2px 5px; font-size:0.8em;">
                                            SL: <?php echo $row['so_luong']; ?>
                                        </span>
                                        
                                        <?php if(count($dsPhong) > 0): ?>
                                            <?php foreach($dsPhong as $p): ?>
                                                <span class="badge" style="background:#d1fae5; color:#065f46; margin-left:3px; border:1px solid #a7f3d0; padding:2px 5px; font-size:0.8em;">P.<?php echo $p; ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td style="padding: 15px; font-size:0.9em;">
                                    <div style="color:green;">IN: <?php echo date('d/m/Y', strtotime($row['ngay_nhan'])); ?></div>
                                    <div style="color:red;">OUT: <?php echo date('d/m/Y', strtotime($row['ngay_tra'])); ?></div>
                                </td>

                                <td style="padding: 15px; font-weight:bold; color:#e67e22;">
                                    <?php echo number_format($row['tong_tien'], 0, ',', '.'); ?>đ
                                </td>
                                
                                <td style="padding: 15px; text-align:center;">
                                    <?php 
                                        $st = trim($row['trang_thai']);
                                        
                                        if ($st == 'Chờ xác nhận') 
                                            echo '<span class="badge" style="background:#f1c40f; color:white; padding:5px 10px; border-radius:4px;">Chờ duyệt</span>';
                                        elseif ($st == 'Đã duyệt') 
                                            echo '<span class="badge" style="background:#3498db; color:white; padding:5px 10px; border-radius:4px;">Đã đặt (Giữ)</span>';
                                        elseif ($st == 'Đang ở') 
                                            echo '<span class="badge" style="background:#e74c3c; color:white; padding:5px 10px; border-radius:4px;">Đang ở</span>';
                                        elseif ($st == 'Đã trả') 
                                            echo '<span class="badge" style="background:#2ecc71; color:white; padding:5px 10px; border-radius:4px;">Hoàn thành</span>';
                                        elseif ($st == 'Hủy do vắng mặt') 
                                            echo '<span class="badge" style="background:#95a5a6; color:white; padding:5px 10px; border-radius:4px;">Vắng mặt</span>';
                                        elseif ($st == 'Đã hủy') 
                                            echo '<span class="badge" style="background:#95a5a6; color:white; padding:5px 10px; border-radius:4px;">Đã hủy</span>';
                                        else 
                                            echo '<span class="badge" style="background:#7f8c8d; color:white; padding:5px 10px; border-radius:4px;">' . $st . '</span>';
                                    ?>
                                </td>

                                <td style="padding: 15px; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        
                                        <?php if ($st == 'Chờ xác nhận'): ?>
                                            <a href="quan_ly_don.php?action=duyet_giu_cho&id=<?php echo $row['id']; ?>" class="action-btn" title="Duyệt & Xếp phòng" style="background:#2ecc71; color:white; padding:6px 10px; border-radius:4px;">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="quan_ly_don.php?action=huy&id=<?php echo $row['id']; ?>" class="action-btn" title="Hủy" onclick="return confirm('Hủy đơn này?')" style="background:#e67e22; color:white; padding:6px 10px; border-radius:4px;">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($st == 'Đã duyệt'): ?>
                                            <a href="quan_ly_don.php?action=check_in&id=<?php echo $row['id']; ?>" class="action-btn" title="Khách đến nhận phòng" onclick="return confirm('Khách đã đến?')" style="background:#3498db; color:white; padding:6px 10px; border-radius:4px;">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <a href="quan_ly_don.php?action=huy&id=<?php echo $row['id']; ?>" class="action-btn" title="Hủy" onclick="return confirm('Hủy đơn?')" style="background:#e67e22; color:white; padding:6px 10px; border-radius:4px;">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="quan_ly_don.php?action=xoa&id=<?php echo $row['id']; ?>" class="action-btn" title="Xóa" onclick="return confirm('Xóa vĩnh viễn?')" style="background:#c0392b; color:white; padding:6px 10px; border-radius:4px;">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px;">Chưa có đơn hàng nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footeradmin.php'; ?>