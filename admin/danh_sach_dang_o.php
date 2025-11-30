<?php
// File: admin/danh_sach_dang_o.php
include __DIR__ . '/../includes/ketnoidb.php';
include __DIR__ . '/../includes/headeradmin.php'; 
?>

<div class="container-fluid" style="margin-top: 30px; max-width: 1200px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary m-0">
            <i class="fa fa-door-open"></i> Danh Sách Khách Đang Lưu Trú
        </h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Về Trang chủ
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead class="bg-dark text-white text-center">
                    <tr>
                        <th width="10%">Mã Đơn</th>
                        <th width="20%">Khách Hàng (Người đặt)</th>
                        <th width="25%">Chi tiết Đoàn</th>
                        <th width="15%">Ngày Check-in</th>
                        <th width="15%">Dự kiến Check-out</th>
                        <th width="15%">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // LẤY CÁC ĐƠN HÀNG ĐANG Ở TRẠNG THÁI 'Đang ở'
                    // Logic: Chỉ cần lấy đơn, không cần lấy từng phòng lẻ
                    $sql = "SELECT dp.id as id_don, 
                                   dp.ten_khach, 
                                   dp.sdt_khach, 
                                   dp.ngay_nhan,
                                   dp.ngay_tra,
                                   dp.so_luong,
                                   p.so_phong, 
                                   lp.ten_loai,
                                   lp.gia_tien
                            FROM dat_phong dp
                            JOIN phong p ON dp.phong_id = p.id
                            JOIN loai_phong lp ON p.loai_phong_id = lp.id
                            WHERE dp.trang_thai = 'Đang ở' -- Chỉ lấy đơn đang có khách ở
                            ORDER BY dp.ngay_nhan ASC";
                    
                    $result = $ketNoiDb->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $ngayNhan = date('d/m/Y', strtotime($row['ngay_nhan']));
                            $ngayTra = date('d/m/Y', strtotime($row['ngay_tra']));
                            
                            // Tính toán hiển thị số phòng
                            $soLuong = $row['so_luong'];
                            $phongChinh = $row['so_phong'];
                            $textPhong = "<strong>P.$phongChinh</strong>";
                            
                            if ($soLuong > 1) {
                                $slPhu = $soLuong - 1;
                                $textPhong .= " <span class='badge badge-info' style='font-size:0.9em'>+ $slPhu phòng cùng loại</span>";
                            }
                            ?>
                            
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="badge badge-secondary" style="font-size: 1.1em;">
                                        #<?php echo $row['id_don']; ?>
                                    </span>
                                </td>

                                <td class="align-middle">
                                    <div style="font-weight:bold; color:#2c3e50; font-size:1.1em;">
                                        <?php echo htmlspecialchars($row['ten_khach']); ?>
                                    </div>
                                    <div style="color:#666;">
                                        <i class="fa fa-phone"></i> <?php echo $row['sdt_khach']; ?>
                                    </div>
                                </td>
                                
                                <td class="align-middle">
                                    <div style="color:#2980b9; font-weight:bold; margin-bottom:5px;">
                                        <?php echo $row['ten_loai']; ?>
                                    </div>
                                    <div><?php echo $textPhong; ?></div>
                                </td>

                                <td class="align-middle text-center" style="color:green;">
                                    <?php echo $ngayNhan; ?>
                                </td>
                                <td class="align-middle text-center" style="color:#e67e22;">
                                    <?php echo $ngayTra; ?>
                                </td>
                                
                                <td class="text-center align-middle">
                                    <a href="thanh_toan.php?id_don=<?php echo $row['id_don']; ?>" 
                                       class="btn btn-danger btn-sm shadow-sm"
                                       title="Tính tiền và Trả toàn bộ phòng của đoàn này">
                                        <i class="fa fa-money-bill-wave"></i> Thanh toán
                                    </a>
                                </td>
                            </tr>

                            <?php
                        }
                    } else {
                        echo '<tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fa fa-check-circle text-success" style="font-size: 3em; margin-bottom: 10px;"></i>
                                    <p class="text-muted mt-2">Hiện tại không có đoàn khách nào đang lưu trú.</p>
                                </td>
                              </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footeradmin.php'; ?>