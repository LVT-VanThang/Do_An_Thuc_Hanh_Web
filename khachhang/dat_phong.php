<?php
// File: khachhang/dat_phong.php

// 1. Kết nối DB và Header
include '../includes/ketnoidb.php';
$tieuDeTrang = "Xác nhận đặt phòng - Khách sạn ABC";
include '../includes/headerkhachhang.php';

// 2. Kiểm tra dữ liệu đầu vào
if (!isset($_GET['id']) || empty($_GET['ngay_nhan']) || empty($_GET['ngay_tra'])) {
    echo "<script>alert('Vui lòng chọn phòng trước!'); window.location.href='index.php';</script>";
    exit;
}

// LẤY ID LOẠI PHÒNG TỪ URL
$idLoaiPhong = (int)$_GET['id']; 

$ngayNhan = $_GET['ngay_nhan'];
$ngayTra = $_GET['ngay_tra'];
$soLuong = isset($_GET['so_luong']) ? (int)$_GET['so_luong'] : 1;

// 3. Lấy thông tin GIÁ TIỀN từ bảng Loại Phòng
$sql = "SELECT * FROM loai_phong WHERE id = $idLoaiPhong";
$ketQua = $ketNoiDb->query($sql);

if ($ketQua->num_rows == 0) {
    die("Lỗi: Loại phòng này không tồn tại.");
}
$phongInfo = $ketQua->fetch_assoc();

// --- Xử lý ảnh ---
if (!empty($phongInfo['anh_dai_dien'])) {
    $anhBase64 = base64_encode($phongInfo['anh_dai_dien']);
    $nguonAnh = 'data:image/jpeg;base64,' . $anhBase64;
} else {
    $nguonAnh = 'images/no-image.jpg';
}

// 4. Tính toán tiền
$date1 = new DateTime($ngayNhan);
$date2 = new DateTime($ngayTra);
$khoangCach = $date1->diff($date2);
$soDem = $khoangCach->days;
if ($soDem <= 0) $soDem = 1;

$tongTien = $phongInfo['gia_tien'] * $soDem * $soLuong;

// 5. XỬ LÝ ĐẶT PHÒNG
$thongBao = "";
$daDatThanhCong = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tenKhach = $_POST['ten_khach'];
    $sdtKhach = $_POST['sdt_khach'];
    $emailKhach = $_POST['email_khach'];

    // --- KIỂM TRA CÒN PHÒNG TRỐNG KHÔNG? (Check Logic) ---
    // Đếm số phòng trống thuộc loại này
    $sqlCheckSlot = "SELECT COUNT(*) as cnt FROM phong 
                     WHERE loai_phong_id = $idLoaiPhong 
                     AND trang_thai = 'Sẵn sàng'";
    $rsCheck = $ketNoiDb->query($sqlCheckSlot)->fetch_assoc();
    
    // Nếu số phòng trống >= số lượng khách đặt thì cho đặt
    if ($rsCheck['cnt'] >= $soLuong) {
        
        // --- CÂU LỆNH INSERT ĐÃ SỬA ---
        // Thay 'phong_id' thành 'loai_phong_id'
        // Thay biến '$idPhongThucTe' thành '$idLoaiPhong'
        
        $sqlInsert = "INSERT INTO dat_phong (loai_phong_id, so_luong, ten_khach, email_khach, sdt_khach, ngay_nhan, ngay_tra, tong_tien, trang_thai) 
                      VALUES ('$idLoaiPhong', '$soLuong', '$tenKhach', '$emailKhach', '$sdtKhach', '$ngayNhan', '$ngayTra', '$tongTien', 'Chờ xác nhận')";
        
        if ($ketNoiDb->query($sqlInsert) === TRUE) {
            $daDatThanhCong = true;
        } else {
            $thongBao = "<p style='color:red; text-align:center;'>Lỗi hệ thống: " . $ketNoiDb->error . "</p>";
        }
        
    } else {
        // Hết phòng
        $thongBao = "<div class='alert alert-danger text-center'>
                        Rất tiếc! Hiện tại chúng tôi chỉ còn <strong>" . $rsCheck['cnt'] . "</strong> phòng trống cho loại này.
                        <br>Vui lòng giảm số lượng hoặc chọn ngày khác.
                     </div>";
    }
}
?>

<main class="container page-padding">
    
    <?php if ($daDatThanhCong): ?>
        <div class="thong-bao-thanh-cong" style="text-align:center; padding: 50px;">
            <i class="fas fa-check-circle" style="font-size: 4rem; color: #27ae60;"></i>
            <h1 class="title-success" style="color: #27ae60; margin-top: 20px;">Đặt phòng thành công!</h1>
            <p class="text-muted">Cảm ơn <strong><?php echo htmlspecialchars($_POST['ten_khach']); ?></strong>.</p>
            <p>Đơn đặt <strong><?php echo $soLuong; ?> phòng</strong> của bạn đang chờ xác nhận.</p>
            <p>Nhân viên sẽ liên hệ sớm nhất qua số: <strong><?php echo htmlspecialchars($_POST['sdt_khach']); ?></strong></p>
            <a href="index.php" class="btn btn-primary" style="margin-top:20px;">Về trang chủ</a>
        </div>
    
    <?php else: ?>
        <h1 class="tieu-de-muc" style="margin-bottom: 30px;">Xác nhận thông tin đặt phòng</h1>
        
        <?php echo $thongBao; ?>

        <div class="dat-phong-wrapper">
            <div class="cot-form">
                <div class="form-box">
                    <h3 class="form-header">Thông tin khách hàng</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Họ và tên:</label>
                            <input type="text" name="ten_khach" class="form-control" required placeholder="Nhập họ tên của bạn">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Số điện thoại:</label>
                            <input type="text" name="sdt_khach" class="form-control" required placeholder="Nhập số điện thoại liên hệ">
                        </div>
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label class="form-label">Email:</label>
                            <input type="email" name="email_khach" class="form-control" required placeholder="Nhập email để nhận vé">
                        </div>
                        <button type="submit" class="btn-submit">HOÀN TẤT ĐẶT PHÒNG</button>
                    </form>
                </div>
            </div>

            <div class="cot-tom-tat">
                <div class="summary-box">
                    <h3 class="summary-title">Tóm tắt yêu cầu</h3>
                    <img src="<?php echo $nguonAnh; ?>" class="summary-thumb">
                    <p style="margin-bottom: 10px; font-size: 1.1rem;"><strong>Loại phòng:</strong> <?php echo $phongInfo['ten_loai']; ?></p>
                    <hr class="divider">
                    <div class="summary-row">
                        <span>Số lượng:</span>
                        <strong><?php echo $soLuong; ?> phòng</strong>
                    </div>
                    <div class="summary-row">
                        <span>Ngày nhận:</span>
                        <strong><?php echo date('d/m/Y', strtotime($ngayNhan)); ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Ngày trả:</span>
                        <strong><?php echo date('d/m/Y', strtotime($ngayTra)); ?></strong>
                    </div>
                    <div class="total-row">
                        <span style="font-size: 1.1rem;">Tổng thanh toán:</span>
                        <span class="total-price"><?php echo number_format($tongTien, 0, ',', '.'); ?> VNĐ</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php 
$ketNoiDb->close();
include '../includes/footerkhachhang.php'; 
?>