<?php
// File: admin/thanh_toan.php
include __DIR__ . '/../includes/ketnoidb.php';
include __DIR__ . '/../includes/headeradmin.php';

// 1. Kiểm tra ID
if (!isset($_GET['id_don']) || empty($_GET['id_don'])) {
    die("Lỗi: Không tìm thấy mã đơn đặt phòng!");
}
$idDon = (int)$_GET['id_don'];

// --- SQL MỚI: Lấy thông tin từ bảng chi tiết ---
// Lấy thông tin chung của đơn và phòng đầu tiên (làm đại diện)
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

// 2. TÍNH TOÁN TIỀN
$ngayNhan = strtotime($donData['ngay_nhan']);
$ngayHienTai = time(); 
$diff = $ngayHienTai - $ngayNhan;
$soNgayO = ceil($diff / (60 * 60 * 24)); 
if ($soNgayO < 1) $soNgayO = 1; 

$giaPhong = $donData['gia_tien'];
$soLuong = (int)$donData['so_luong']; 
$tongTien = $giaPhong * $soNgayO * $soLuong;

// --- XỬ LÝ POST (THANH TOÁN) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tongTienFinal = $_POST['tong_tien'];
    $ngayTraThucTe = date('Y-m-d H:i:s');

    // 1. Trả tất cả các phòng của đơn này về 'Sẵn sàng'
    // Lấy danh sách phòng từ bảng chi tiết
    $sqlGetRooms = "SELECT phong_id FROM chi_tiet_dat_phong WHERE dat_phong_id = $idDon";
    $resRooms = $ketNoiDb->query($sqlGetRooms);
    
    while($r = $resRooms->fetch_assoc()){
        $pid = $r['phong_id'];
        $ketNoiDb->query("UPDATE phong SET trang_thai = 'Sẵn sàng' WHERE id = $pid");
    }

    // 2. Cập nhật trạng thái đơn hàng -> 'Đã trả'
    $sqlDon = "UPDATE dat_phong 
               SET tong_tien = '$tongTienFinal', 
                   ngay_tra_thuc_te = '$ngayTraThucTe', 
                   trang_thai = 'Đã trả' 
               WHERE id = $idDon";
    
    $ketNoiDb->query($sqlDon);

    // 3. (Tùy chọn) Xóa chi tiết phòng để dọn dẹp sau khi trả
    // $ketNoiDb->query("DELETE FROM chi_tiet_dat_phong WHERE dat_phong_id = $idDon");

    echo "<script>
            alert('Thanh toán thành công! Đã trả $soLuong phòng về trạng thái Sẵn sàng.'); 
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
    .invoice-header .meta { text-align: right; font-size: 14px; opacity: 0.9; }
    
    .invoice-body { padding: 40px; }
    
    .info-row { display: flex; justify-content: space-between; margin-bottom: 40px; }
    .info-col { width: 48%; }
    .info-col h3 { font-size: 16px; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; text-transform: uppercase; }
    .info-col p { margin: 5px 0; font-size: 14px; line-height: 1.6; }
    .info-col strong { color: #333; }
    
    .badge-custom {
        background: #e3f2fd; color: #1565c0; 
        padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;
        border: 1px solid #bbdefb;
    }

    .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .invoice-table th { background-color: #f8f9fa; color: #333; font-weight: bold; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; }
    .invoice-table td { padding: 12px; border-bottom: 1px solid #eee; }
    .invoice-table td.total-label { text-align: right; font-weight: bold; font-size: 16px; color: #333; }
    .invoice-table td.total-amount { text-align: right; font-weight: bold; font-size: 20px; color: #c0392b; }

    .actions { text-align: right; margin-top: 30px; border-top: 1px dashed #ddd; padding-top: 20px; }
    .btn-back { 
        background: #95a5a6; color: white; padding: 12px 25px; 
        text-decoration: none; border-radius: 30px; font-weight: bold; margin-right: 10px; transition: 0.3s;
    }
    .btn-pay { 
        background: #27ae60; color: white; padding: 12px 40px; border: none;
        border-radius: 30px; font-weight: bold; font-size: 16px; cursor: pointer;
        box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2); transition: 0.3s;
    }
    .btn-pay:hover { background: #219150; transform: translateY(-2px); }
    
    i { margin-right: 5px; }
</style>

<div class="invoice-box">
    <div class="invoice-header">
        <h2><i class="fas fa-file-invoice"></i> Hóa Đơn</h2>
        <div class="meta">
            Mã đơn: #<?php echo $idDon; ?><br>
            Ngày: <?php echo date('d/m/Y'); ?>
        </div>
    </div>

    <div class="invoice-body">
        
        <div class="info-row">
            <div class="info-col">
                <h3>Khách hàng</h3>
                <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($donData['ten_khach']); ?></p>
                <p><strong>SĐT:</strong> <?php echo $donData['sdt_khach']; ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($donData['email_khach']); ?></p>
            </div>
            <div class="info-col" style="text-align: right;">
                <h3>Chi tiết phòng</h3>
                <p><strong>Loại:</strong> <?php echo $donData['ten_loai']; ?></p>
                <p>
                    <strong>Số lượng:</strong> 
                    <span class="badge-custom"><?php echo $soLuong; ?> phòng</span>
                </p>
                <p><strong>Phòng đại diện:</strong> P.<?php echo $donData['so_phong_dai_dien'] ?? 'Chưa xếp'; ?></p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Nội dung</th>
                    <th style="text-align: center;">Chi tiết</th>
                    <th style="text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Tiền phòng<br>
                        <small style="color:#888;">(<?php echo date('d/m', $ngayNhan); ?> - <?php echo date('d/m', $ngayHienTai); ?>)</small>
                    </td>
                    <td style="text-align: center;">
                        <?php echo number_format($giaPhong); ?>đ x <?php echo $soNgayO; ?> đêm x <?php echo $soLuong; ?> phòng
                    </td>
                    <td style="text-align: right;">
                        <?php echo number_format($tongTien); ?> ₫
                    </td>
                </tr>
                <tr style="background-color: #fcfcfc;">
                    <td colspan="2" class="total-label">TỔNG THANH TOÁN:</td>
                    <td class="total-amount"><?php echo number_format($tongTien, 0, ',', '.'); ?> ₫</td>
                </tr>
            </tbody>
        </table>

        <form method="POST" class="actions">
            <input type="hidden" name="tong_tien" value="<?php echo $tongTien; ?>">
            <input type="hidden" name="so_luong" value="<?php echo $soLuong; ?>">
            
            <a href="danh_sach_dang_o.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <button type="submit" class="btn-pay" onclick="return confirm('Xác nhận khách đã thanh toán đủ?');">
                <i class="fas fa-check-circle"></i> XÁC NHẬN TRẢ PHÒNG
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; font-size: 13px; color: #aaa;">
            Cảm ơn quý khách đã sử dụng dịch vụ của Khách sạn ABC.
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footeradmin.php'; ?>