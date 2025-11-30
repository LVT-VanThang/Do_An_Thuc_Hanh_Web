<?php
// File: admin/danh_sach_dang_o.php
include __DIR__ . '/../includes/ketnoidb.php';
include __DIR__ . '/../includes/headeradmin.php'; 
?>

<main class="container page-padding">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h1 class="tieu-de-muc" style="text-align:left; margin:0; color:#333;">Khách đang lưu trú</h1>
            <p style="color:#666; margin-top:5px;">Quản lý danh sách khách và thủ tục trả phòng</p>
        </div>
        
        <a href="quan_ly_don.php" class="btn-big-cta" style="padding:10px 20px; font-size:1rem; background:#95a5a6; border:none; color:white; text-decoration:none; border-radius:4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <i class="fas fa-arrow-left"></i> Về trang chủ
        </a>
    </div>

    <div class="table-card" style="background:white; border-radius:8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow:hidden;">
        <table class="modern-table" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f8f9fa; text-align: left;">
                    <th style="padding:15px; border-bottom:2px solid #eee; width:10%;">Mã Đơn</th>
                    <th style="padding:15px; border-bottom:2px solid #eee; width:20%;">Khách Hàng</th>
                    <th style="padding:15px; border-bottom:2px solid #eee; width:30%;">Danh sách Phòng</th>
                    <th style="padding:15px; border-bottom:2px solid #eee; width:15%;">Thời gian</th>
                    <th style="padding:15px; border-bottom:2px solid #eee; width:10%; text-align:center;">Dự kiến ra</th>
                    <th style="padding:15px; border-bottom:2px solid #eee; width:15%; text-align:center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // SQL MỚI: Lấy dữ liệu từ bảng chi tiết
                // Sử dụng GROUP_CONCAT để gom tất cả số phòng vào 1 dòng
                $sql = "SELECT dp.id as id_don, 
                               dp.ten_khach, 
                               dp.sdt_khach, 
                               dp.ngay_nhan,
                               dp.ngay_tra,
                               dp.so_luong,
                               lp.ten_loai,
                               lp.gia_tien,
                               GROUP_CONCAT(p.so_phong ORDER BY p.so_phong ASC SEPARATOR ', ') as danh_sach_phong
                        FROM dat_phong dp
                        JOIN loai_phong lp ON dp.loai_phong_id = lp.id
                        LEFT JOIN chi_tiet_dat_phong ct ON dp.id = ct.dat_phong_id
                        LEFT JOIN phong p ON ct.phong_id = p.id
                        WHERE dp.trang_thai = 'Đang ở'
                        GROUP BY dp.id
                        ORDER BY dp.ngay_nhan ASC";
                
                $result = $ketNoiDb->query($sql);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $ngayNhan = date('d/m/Y', strtotime($row['ngay_nhan']));
                        $ngayTra = date('d/m/Y', strtotime($row['ngay_tra']));
                        $soLuong = $row['so_luong'];
                        
                        // Tách chuỗi danh sách phòng thành mảng để hiển thị đẹp
                        $phongs = explode(', ', $row['danh_sach_phong']);
                        ?>
                        
                        <tr style="border-bottom: 1px solid #eee; transition: background 0.2s;">
                            
                            <td style="padding:15px; font-weight:bold; color:#7f8c8d;">
                                #<?php echo $row['id_don']; ?>
                            </td>

                            <td style="padding:15px;">
                                <div style="font-weight:bold; color:#2c3e50; font-size:1.05em;">
                                    <?php echo htmlspecialchars($row['ten_khach']); ?>
                                </div>
                                <div style="color:#666; font-size:0.9em; margin-top:3px;">
                                    <i class="fas fa-phone-alt" style="font-size:0.8em; color:#999;"></i> <?php echo $row['sdt_khach']; ?>
                                </div>
                            </td>
                            
                            <td style="padding:15px;">
                                <div style="color:#2980b9; font-weight:600; margin-bottom:8px;">
                                    <?php echo $row['ten_loai']; ?>
                                    <span class="badge" style="background:#eee; color:#555; padding:2px 6px; border-radius:4px; font-size:0.8em;">SL: <?php echo $soLuong; ?></span>
                                </div>
                                
                                <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                    <?php if (!empty($row['danh_sach_phong'])): ?>
                                        <?php foreach($phongs as $p): ?>
                                            <span style="background:#e0f2f1; color:#00695c; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:0.9em; border:1px solid #b2dfdb;">
                                                <i class="fas fa-key" style="font-size:0.8em"></i> P.<?php echo $p; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color:#999; font-style:italic;">Chưa xếp phòng</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td style="padding:15px; font-size:0.95em;">
                                <div style="color:#27ae60; font-weight:500;">
                                    <i class="fas fa-sign-in-alt"></i> <?php echo $ngayNhan; ?>
                                </div>
                                <small style="color:#999;">
                                    (Đã ở: <?php echo ceil((time() - strtotime($row['ngay_nhan'])) / 86400); ?> ngày)
                                </small>
                            </td>

                            <td style="padding:15px; text-align:center; font-size:0.95em; color:#e67e22; font-weight:500;">
                                <?php echo $ngayTra; ?>
                            </td>
                            
                            <td style="padding:15px; text-align:center;">
                                <a href="thanh_toan.php?id_don=<?php echo $row['id_don']; ?>" 
                                   class="btn-action"
                                   style="display:inline-block; background:#e74c3c; color:white; padding:8px 15px; border-radius:4px; text-decoration:none; font-weight:500; font-size:0.9em; box-shadow:0 2px 4px rgba(231,76,60,0.3); transition:0.2s;"
                                   onmouseover="this.style.background='#c0392b'"
                                   onmouseout="this.style.background='#e74c3c'"
                                   title="Tính tiền và trả phòng">
                                    <i class="fas fa-file-invoice-dollar"></i> Thanh toán
                                </a>
                            </td>
                        </tr>

                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:50px 20px;">
                            <div style="color:#bdc3c7; margin-bottom:15px;">
                                <i class="fas fa-bed" style="font-size:3em;"></i>
                            </div>
                            <h4 style="color:#7f8c8d; margin:0;">Hiện không có khách nào đang lưu trú</h4>
                            <p style="color:#95a5a6; font-size:0.9em;">Các đơn đặt phòng mới sẽ xuất hiện tại đây sau khi Check-in.</p>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/../includes/footeradmin.php'; ?>