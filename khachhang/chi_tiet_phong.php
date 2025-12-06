<?php
// 1. Kết nối DB và Header
include __DIR__ . '/../includes/ketnoidb.php';
$tieuDeTrang = "Chi tiết phòng - Khách sạn ABC";
include __DIR__ . '/../includes/headerkhachhang.php';

// 2. Lấy ID phòng từ URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Nếu không có ID thì về trang chủ
    header('Location: index.php');
    exit;
}

$idPhong = (int)$_GET['id'];

// --- DEBUG: Kiểm tra xem code nhận đúng ID chưa (Xóa sau khi sửa xong) ---
// echo "ID đang xem: " . $idPhong; 

// 3. Truy vấn thông tin phòng (QUAN TRỌNG: PHẢI CÓ WHERE)
$sql = "SELECT * FROM loai_phong WHERE id = $idPhong";
$ketQua = $ketNoiDb->query($sql);

// Kiểm tra xem phòng có tồn tại không
if ($ketQua->num_rows == 0) {
    echo "<div class='container' style='padding:50px; text-align:center;'>
            <h2>Không tìm thấy phòng có ID = $idPhong!</h2>
            <a href='index.php' class='btn-book-now' style='width:200px; margin:20px auto;'>Quay lại</a>
          </div>";
    include __DIR__ . '/../includes/footerkhachhang.php';
    exit;
}

// 4. Lấy dữ liệu 1 dòng duy nhất (KHÔNG DÙNG VÒNG LẶP WHILE)
$phong = $ketQua->fetch_assoc();

// --- Xử lý ảnh BLOB ---
if (!empty($phong['anh_dai_dien'])) {
    $anhBase64 = base64_encode($phong['anh_dai_dien']);
    $nguonAnh = 'data:image/jpeg;base64,' . $anhBase64;
} else {
    $nguonAnh = '../images/no-image.jpg'; // Chú ý đường dẫn ảnh
}

// --- LẤY NGÀY HIỆN TẠI ĐỂ CHẶN LỊCH ---
$ngayHienTai = date('Y-m-d');   
?>

<main class="container page-padding">
    
    <div class="detail-wrapper">
        
        <div class="col-image">
            <img src="<?php echo $nguonAnh; ?>" alt="<?php echo $phong['ten_loai']; ?>" class="detail-img">
            
            <div class="desc-box">
                <h3 class="desc-title">Mô tả phòng:</h3>
                <p><?php echo $phong['mo_ta']; ?></p>
            </div>
        </div>

        <div class="col-info">
            <h1 class="room-title-large"><?php echo $phong['ten_loai']; ?></h1>
            
            <p class="room-price-large">
                <?php echo number_format($phong['gia_tien'], 0, ',', '.'); ?> VNĐ 
                <span class="price-unit">/ đêm</span>
            </p>

            <div class="specs-box">
                <p class="specs-item">
                    <i class="fas fa-user-friends specs-icon"></i> 
                    <strong>Sức chứa:</strong> <?php echo $phong['suc_chua']; ?> người lớn
                </p>
                <p class="specs-item">
                    <i class="fas fa-bed specs-icon"></i> 
                    <strong>Giường:</strong> <?php echo $phong['so_giuong']; ?> giường
                </p>
                <p class="specs-item">
                    <i class="fas fa-eye specs-icon"></i> 
                    <strong>View:</strong> <?php echo $phong['huong_nhin']; ?>
                </p>
                
                <?php
                    // Đếm số phòng trống thực tế
                    $sqlCount = "SELECT COUNT(*) as soluong FROM phong WHERE loai_phong_id = $idPhong AND trang_thai = 'Sẵn sàng'";
                    $countResult = $ketNoiDb->query($sqlCount)->fetch_assoc();
                    $soLuongCon = $countResult['soluong'];
                ?>
                <p class="specs-item">
                    <i class="fas fa-check-circle specs-icon"></i> 
                    <strong>Phòng trống:</strong> <?php echo $soLuongCon; ?> phòng
                </p>
            </div>

            <div class="booking-widget">
                <h3 class="widget-title">Đặt phòng này ngay</h3>
                <form action="dat_phong.php" method="GET" id="bookingForm">
                    <input type="hidden" name="id" value="<?php echo $phong['id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Ngày nhận:</label>
                        <input type="date" name="ngay_nhan" id="ngay_nhan" 
                               min="<?php echo $ngayHienTai; ?>" 
                               required class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ngày trả:</label>
                        <input type="date" name="ngay_tra" id="ngay_tra" 
                               min="<?php echo $ngayHienTai; ?>" 
                               required class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Số lượng phòng:</label>
                        <input type="number" name="so_luong" value="1" min="1" 
                               max="<?php echo ($soLuongCon > 0) ? $soLuongCon : 1; ?>" 
                               required class="form-input">
                    </div>
                    
                    <?php if($soLuongCon > 0): ?>
                        <button type="submit" class="btn-book-now">
                            Tiến hành đặt phòng
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-book-now" style="background:#999; cursor:not-allowed;" disabled>
                            Hết phòng
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

</main>

<script>
    // Script đơn giản để chặn ngày trả < ngày nhận
    const ngayNhan = document.getElementById('ngay_nhan');
    const ngayTra = document.getElementById('ngay_tra');

    ngayNhan.addEventListener('change', function() {
        const d = new Date(this.value);
        d.setDate(d.getDate() + 1); // Ngày trả tối thiểu là hôm sau
        ngayTra.min = d.toISOString().split('T')[0];
        
        if(ngayTra.value && ngayTra.value < ngayTra.min) {
            ngayTra.value = ngayTra.min;
        }
    });
</script>

<?php
$ketNoiDb->close();
include __DIR__ . '/../includes/footerkhachhang.php';
?>