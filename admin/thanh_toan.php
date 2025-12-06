<?php
// File: admin/thanh_toan.php
include __DIR__ . '/../includes/ketnoidb.php';
include __DIR__ . '/../includes/headeradmin.php';

// 1. Kiểm tra ID
if (!isset($_GET['id_don']) || empty($_GET['id_don'])) {
    die("Lỗi: Không tìm thấy mã đơn đặt phòng!");
}
$idDon = (int)$_GET['id_don'];

// --- LẤY DỮ LIỆU ĐỂ TÍNH TOÁN ---
// Lấy thêm cột tien_coc
$sql = "SELECT dp.*, 
               lp.ten_loai, 
               lp.gia_tien, 
               lp.id as id_loai_phong,
               (SELECT so_phong FROM phong p 
                JOIN chi_tiet_dat_phong ct ON p.id = ct.phong_id 
                WHERE ct.dat_phong_id = dp.id LIMIT 1) as so_phong_dai_dien
        FROM dat_phong dp
        JOIN loai_phong lp ON dp.loai_phong_id = lp.id
        WHERE dp.id = $idDon";

$result = $ketNoiDb->query($sql);

if ($result->num_rows == 0) {
    die("<div class='container mt-5 text-center'><h3>Không tìm thấy đơn đặt phòng này!</h3></div>");
}

$donData = $result->fetch_assoc();

// 2. TÍNH TOÁN TIỀN (Theo thực tế sử dụng)
$ngayNhan = strtotime($donData['ngay_nhan']);
$ngayHienTai = time(); 
$diff = $ngayHienTai - $ngayNhan;
$soNgayO = ceil($diff / (60 * 60 * 24)); 
if ($soNgayO < 1) $soNgayO = 1; 

$giaPhong = $donData['gia_tien'];
$soLuong = (int)$donData['so_luong']; 

// A. Tổng tiền phòng (Giá x Ngày x Số lượng)
$tongTienPhong = $giaPhong * $soNgayO * $soLuong;

// B. Tiền đã cọc (Lấy từ DB)
$tienDaCoc = $donData['tien_coc'];

// C. Tiền còn phải thu
$tienConLai = $tongTienPhong - $tienDaCoc;


// --- XỬ LÝ KHI BẤM NÚT "XÁC NHẬN THANH TOÁN" ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lưu tổng doanh thu cuối cùng (Tổng tiền phòng, không phải tiền còn lại)
    $tongDoanhThu = $_POST['tong_tien_phong']; 
    $ngayTraThucTe = date('Y-m-d H:i:s');

    // 1. Trả các phòng về 'Sẵn sàng'
    $sqlGetRooms = "SELECT phong_id FROM chi_tiet_dat_phong WHERE dat_phong_id = $idDon";
    $resRooms = $ketNoiDb->query($sqlGetRooms);
    while($r = $resRooms->fetch_assoc()){
        $pid = $r['phong_id'];
        $ketNoiDb->query("UPDATE phong SET trang_thai = 'Sẵn sàng' WHERE id = $pid");
    }

    // 2. Cập nhật đơn hàng
    $sqlDon = "UPDATE dat_phong 
               SET tong_tien = '$tongDoanhThu', 
                   ngay_tra_thuc_te = '$ngayTraThucTe', 
                   trang_thai = 'Đã trả' 
               WHERE id = $idDon";
    
    $ketNoiDb->query($sqlDon);

    // 3. Dọn dẹp bảng chi tiết
    $ketNoiDb->query("DELETE FROM chi_tiet_dat_phong WHERE dat_phong_id = $idDon");

    echo "<script>
            alert('Thanh toán thành công! Đã thu nốt " . number_format($tienConLai) . " VNĐ.'); 
            window.location.href='danh_sach_dang_o.php';
          </script>";
    exit;
}
?>

<style>
    body { background-color: #f4f7f6; }
    .invoice-box {
        max-width: 800px;
        margin: 40px auto;
        padding: 0;
        border: 1px solid #eee;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        background-color: #fff;
        border-radius: 8px;
        font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        color: #555;
        overflow: hidden;
    }
    .invoice-header {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
        padding: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .invoice-header h2 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
    
    .invoice-body { padding: 40px; }
    
    .info-row { display: flex; justify-content: space-between; margin-bottom: 30px; }
    .info-col h3 { font-size: 16px; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
    
    .badge-custom { background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }

    .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .invoice-table th { background-color: #f8f9fa; color: #333; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; }
    .invoice-table td { padding: 12px; border-bottom: 1px solid #eee; }
    
    /* Style cho các dòng tổng tiền */
    .row-total td { text-align: right; font-size: 1.1rem; }
    .row-deposit td { text-align: right; color: #27ae60; }
    .row-final td { text-align: right; font-size: 1.4rem; font-weight: bold; color: #c0392b; border-top: 2px solid #ddd; }

    .actions { text-align: right; margin-top: 30px; border-top: 1px dashed #ddd; padding-top: 20px; }
    .btn-back { background: #95a5a6; color: white; padding: 12px 25px; text-decoration: none; border-radius: 30px; font-weight: bold; margin-right: 10px; }
    .btn-pay { background: #27ae60; color: white; padding: 12px 40px; border: none; border-radius: 30px; font-weight: bold; font-size: 16px; cursor: pointer; box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2); }
    .btn-pay:hover { background: #219150; transform: translateY(-2px); }
</style>

<div class="invoice-box">
    <div class="invoice-header">
        <h2><i class="fas fa-file-invoice"></i> Quyết Toán Phòng</h2>
        <div style="text-align: right; opacity: 0.9;">
            Mã đơn: #<?php echo $idDon; ?><br>
            Ngày: <?php echo date('d/m/Y'); ?>
        </div>
    </div>

    <div class="invoice-body">
        
        <div class="info-row">
            <div class="info-col" style="width: 50%;">
                <h3>Khách hàng</h3>
                <p><strong><?php echo htmlspecialchars($donData['ten_khach']); ?></strong></p>
                <p><i class="fas fa-phone"></i> <?php echo $donData['sdt_khach']; ?></p>
            </div>
            <div class="info-col" style="width: 50%; text-align: right;">
                <h3>Phòng & Thời gian</h3>
                <p><?php echo $donData['ten_loai']; ?> <span class="badge-custom">SL: <?php echo $soLuong; ?></span></p>
                <p>
                    <?php echo date('d/m', $ngayNhan); ?> <i class="fas fa-arrow-right"></i> <?php echo date('d/m', $ngayHienTai); ?>
                    (<strong><?php echo $soNgayO; ?> đêm</strong>)
                </p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Khoản mục</th>
                    <th style="text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-total">
                    <td>Tổng tiền phòng <small>(<?php echo number_format($giaPhong); ?>đ x <?php echo $soLuong; ?> phòng x <?php echo $soNgayO; ?> đêm)</small></td>
                    <td><?php echo number_format($tongTienPhong); ?> ₫</td>
                </tr>

                <tr class="row-deposit">
                    <td><i class="fas fa-check-circle"></i> Đã đặt cọc trước (30%)</td>
                    <td>- <?php echo number_format($tienDaCoc); ?> ₫</td>
                </tr>

                <tr class="row-final">
                    <td>CÒN PHẢI THU:</td>
                    <td><?php echo number_format($tienConLai); ?> ₫</td>
                </tr>
            </tbody>
        </table>

        <form method="POST" class="actions">
            <input type="hidden" name="tong_tien_phong" value="<?php echo $tongTienPhong; ?>">
            
            <a href="danh_sach_dang_o.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <button type="submit" class="btn-pay" onclick="return confirm('Xác nhận đã thu đủ số tiền còn lại: <?php echo number_format($tienConLai); ?> đ?');">
                <i class="fas fa-cash-register"></i> THU TIỀN & TRẢ PHÒNG
            </button>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../includes/footeradmin.php'; ?>