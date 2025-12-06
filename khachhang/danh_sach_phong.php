<?php
// File: khachhang/danh_sach_phong.php
include '../includes/ketnoidb.php';
$tieuDeTrang = "Danh sách phòng - Khách sạn ABC";
include '../includes/headerkhachhang.php';

// 1. NHẬN DỮ LIỆU TỪ BỘ LỌC (GET)
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$sucChua = isset($_GET['suc_chua']) && $_GET['suc_chua'] != 'all' ? (int)$_GET['suc_chua'] : 0;
$soGiuong = isset($_GET['so_giuong']) && $_GET['so_giuong'] != 'all' ? (int)$_GET['so_giuong'] : 0;
$view = isset($_GET['view']) && $_GET['view'] != 'all' ? $_GET['view'] : '';
$mucGia = isset($_GET['muc_gia']) ? $_GET['muc_gia'] : 'all';

// 2. TẠO CÂU SQL LỌC LOẠI PHÒNG (Cơ bản)
$sql = "SELECT * FROM loai_phong WHERE 1=1";

if ($sucChua > 0) $sql .= " AND suc_chua >= $sucChua";
if ($soGiuong > 0) $sql .= " AND so_giuong >= $soGiuong";
if ($view != '') {
    $v = $ketNoiDb->real_escape_string($view);
    $sql .= " AND huong_nhin LIKE '%$v%'";
}
if ($mucGia != 'all') {
    if ($mucGia == 'duoi-2tr') $sql .= " AND gia_tien < 2000000";
    elseif ($mucGia == '2tr-3tr') $sql .= " AND gia_tien BETWEEN 2000000 AND 3000000";
    elseif ($mucGia == 'tren-3tr') $sql .= " AND gia_tien > 3000000";
}

$resLoai = $ketNoiDb->query($sql);
?>

<main>
    <div class="thanh-tim-kiem-sticky" style="background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 15px 0; position: sticky; top: 0; z-index: 999;">
        <div class="container">
            <form action="danh_sach_phong.php" method="GET" class="form-tim-kiem-ngang" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                
                <div class="input-item" style="flex: 1; min-width: 150px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Ngày nhận:</label>
                    <input type="date" name="checkin" id="checkin" 
                           class="form-control" 
                           value="<?php echo $checkin; ?>" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <div class="input-item" style="flex: 1; min-width: 150px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Ngày trả:</label>
                    <input type="date" name="checkout" id="checkout" 
                           class="form-control" 
                           value="<?php echo $checkout; ?>" 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <div class="input-item">
                    <label>Số người:</label>
                    <select name="suc_chua" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="all">Tất cả</option>
                        <option value="1" <?php if($sucChua == 1) echo 'selected'; ?>>1+</option>
                        <option value="2" <?php if($sucChua == 2) echo 'selected'; ?>>2+</option>
                        <option value="4" <?php if($sucChua == 4) echo 'selected'; ?>>4+</option>
                    </select>
                </div>

                <div class="input-item">
                    <label>Giá:</label>
                    <select name="muc_gia" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="all">Tất cả</option>
                        <option value="duoi-2tr" <?php if($mucGia == 'duoi-2tr') echo 'selected'; ?>>&lt; 2tr</option>
                        <option value="2tr-3tr" <?php if($mucGia == '2tr-3tr') echo 'selected'; ?>>2-3tr</option>
                        <option value="tren-3tr" <?php if($mucGia == 'tren-3tr') echo 'selected'; ?>>&gt; 3tr</option>
                    </select>
                </div>

                <button type="submit" class="btn-filter" style="background: #d4af37; color: white; border: none; padding: 10px 25px; border-radius: 4px; font-weight: bold; height: 38px; cursor: pointer;">
                    <i class="fas fa-search"></i> TÌM
                </button>
                
                <a href="danh_sach_phong.php" class="btn-reset" title="Xóa bộ lọc" style="color: #999; align-self: center; margin-left: 10px;">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </form>
        </div>
    </div>

    <div class="container page-padding" style="margin-top: 40px; margin-bottom: 60px;">
        
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 class="tieu-de-muc">Kết quả tìm kiếm</h2>
            <?php if($checkin && $checkout): ?>
                <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; display: inline-block; border-radius: 5px;">
                    <i class="fas fa-check-circle"></i> Đang tìm từ <b><?php echo date('d/m', strtotime($checkin)); ?></b> đến <b><?php echo date('d/m', strtotime($checkout)); ?></b>
                </div>
            <?php else: ?>
                <div style="background: #fff3e0; color: #e65100; padding: 10px; display: inline-block; border-radius: 5px;">
                    <i class="fas fa-exclamation-circle"></i> Vui lòng chọn <b>Ngày nhận</b> và <b>Ngày trả</b> để xem giá và đặt phòng.
                </div>
            <?php endif; ?>
        </div>

        <div class="luoi-phong" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
            <?php
            if ($resLoai->num_rows > 0) {
                while($row = $resLoai->fetch_assoc()) {
                    $idLoai = $row['id'];
                    $anh = !empty($row['anh_dai_dien']) ? 'data:image/jpeg;base64,' . base64_encode($row['anh_dai_dien']) : '../images/no-image.jpg';
                    
                    // --- LOGIC KIỂM TRA PHÒNG TRỐNG ---
                    $phongTrong = 0;
                    $showButtonDat = false;

                    // Chỉ kiểm tra nếu đã chọn ngày
                    if ($checkin && $checkout) {
                        $dkBaoTri = (strtotime($checkin) <= time()) ? "AND trang_thai != 'Bảo trì'" : "";
                        
                        $sqlAll = "SELECT id FROM phong WHERE loai_phong_id = $idLoai $dkBaoTri";
                        $resAll = $ketNoiDb->query($sqlAll);
                        
                        while($rRoom = $resAll->fetch_assoc()) {
                            $pid = $rRoom['id'];
                            $sqlCheck = "SELECT COUNT(*) as cnt 
                                         FROM chi_tiet_dat_phong ct
                                         JOIN dat_phong dp ON ct.dat_phong_id = dp.id
                                         WHERE ct.phong_id = $pid
                                         AND dp.trang_thai IN ('Đã duyệt', 'Đang ở')
                                         AND (dp.ngay_nhan < '$checkout' AND dp.ngay_tra > '$checkin')";
                            
                            if ($ketNoiDb->query($sqlCheck)->fetch_assoc()['cnt'] == 0) {
                                $phongTrong++;
                            }
                        }
                        if ($phongTrong > 0) $showButtonDat = true;
                    }
            ?>
                    <div class="the-phong" style="background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #eee; display: flex; flex-direction: column;">
                        <div class="khung-anh" style="height: 220px; overflow: hidden;">
                            <img src="<?php echo $anh; ?>" alt="<?php echo $row['ten_loai']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        
                        <div class="noi-dung-phong" style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                            <h3 style="font-size: 1.2rem; font-weight: bold; color: #2c3e50; margin: 0 0 10px;"><?php echo $row['ten_loai']; ?></h3>
                            
                            <div class="thong-tin-phu" style="color:#7f8c8d; font-size:0.9rem; margin-bottom:15px;">
                                <span><i class="fas fa-user"></i> <?php echo $row['suc_chua']; ?></span> | 
                                <span><i class="fas fa-bed"></i> <?php echo $row['so_giuong']; ?></span> | 
                                <span><i class="fas fa-eye"></i> <?php echo $row['huong_nhin']; ?></span>
                            </div>
                            
                            <div style="font-size: 1.3rem; color: #d4af37; font-weight: bold; margin-bottom: 5px;">
                                <?php echo number_format($row['gia_tien'], 0, ',', '.'); ?> VNĐ <span style="font-size:0.8em; color:#999; font-weight:normal;">/đêm</span>
                            </div>
                            
                            <?php if ($checkin && $checkout): ?>
                                <?php if ($phongTrong > 0): ?>
                                    <div style="color: green; font-size: 0.9rem; margin-bottom: 20px;">
                                        <i class="fas fa-check-circle"></i> Còn <?php echo $phongTrong; ?> phòng trống
                                    </div>
                                    <a href="dat_phong.php?id=<?php echo $idLoai; ?>&checkin=<?php echo $checkin; ?>&checkout=<?php echo $checkout; ?>&soluong=1" 
                                       class="nut-xem-phong"
                                       style="display: block; text-align: center; background: #27ae60; color: white; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; margin-top: auto;">
                                        ĐẶT NGAY
                                    </a>
                                <?php else: ?>
                                    <div style="color: red; font-size: 0.9rem; margin-bottom: 20px;">
                                        <i class="fas fa-times-circle"></i> Hết phòng
                                    </div>
                                    <button disabled style="width: 100%; background: #eee; color: #999; border: none; padding: 10px; border-radius: 4px; font-weight: bold; margin-top: auto; cursor: not-allowed;">HẾT PHÒNG</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="color: #f39c12; font-size: 0.9rem; margin-bottom: 20px;">
                                    <i class="fas fa-info-circle"></i> Chọn ngày để xem
                                </div>
                                <a href="#" onclick="document.getElementById('checkin').focus(); return false;"
                                   style="display: block; text-align: center; border: 1px solid #d4af37; color: #d4af37; padding: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; margin-top: auto;">
                                    CHỌN NGÀY
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
            <?php 
                }
            } else {
                echo "<p style='text-align:center; width:100%;'>Không tìm thấy phòng nào phù hợp.</p>";
            }
            ?>
        </div>
    </div>
</main>

<script>
    // JS Tự động chỉnh ngày
    const inDate = document.getElementById('checkin');
    const outDate = document.getElementById('checkout');

    inDate.addEventListener('change', function() {
        const d = new Date(this.value);
        d.setDate(d.getDate() + 1);
        outDate.min = d.toISOString().split('T')[0];
        if(outDate.value <= this.value) {
            outDate.value = outDate.min;
        }
    });
</script>

<?php 
$ketNoiDb->close();
include '../includes/footerkhachhang.php'; 
?>