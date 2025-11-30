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
// Lấy thêm so_luong từ bảng dat_phong
$sql = "SELECT dp.*, p.so_phong, lp.gia_tien, lp.ten_loai, lp.id as id_loai_phong
        FROM dat_phong dp
        JOIN phong p ON dp.phong_id = p.id
        JOIN loai_phong lp ON p.loai_phong_id = lp.id
        WHERE dp.id = $idDon";

$result = $ketNoiDb->query($sql);
if ($result->num_rows == 0) {
    die("Không tìm thấy đơn đặt phòng này (Hoặc đã thanh toán rồi).");
}
$donData = $result->fetch_assoc();

// 2. TÍNH TOÁN TIỀN
// A. Tính số ngày
$ngayNhan = strtotime($donData['ngay_nhan']);
$ngayHienTai = time(); 
$diff = $ngayHienTai - $ngayNhan;
$soNgayO = ceil($diff / (60 * 60 * 24)); 
if ($soNgayO < 1) $soNgayO = 1; // Tối thiểu tính 1 ngày

// B. Lấy đơn giá và số lượng
$giaPhong = $donData['gia_tien'];
$soLuong = (int)$donData['so_luong']; // <--- QUAN TRỌNG: Lấy số lượng phòng

// C. Công thức tính tổng tiền
$tongTien = $giaPhong * $soNgayO * $soLuong;


// --- XỬ LÝ KHI BẤM NÚT "XÁC NHẬN THANH TOÁN" ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tongTienFinal = $_POST['tong_tien'];
    $phongChinhId = $_POST['phong_id'];
    $idLoaiPhong = $_POST['loai_phong_id'];
    $slPhong = (int)$_POST['so_luong'];
    $ngayTraThucTe = date('Y-m-d H:i:s');

    // 1. Cập nhật đơn đặt phòng (Kết thúc đơn)
    $sqlDon = "UPDATE dat_phong 
               SET tong_tien = '$tongTienFinal', 
                   ngay_tra_thuc_te = '$ngayTraThucTe', 
                   trang_thai = 'Đã trả' 
               WHERE id = $idDon";
    $ketNoiDb->query($sqlDon);
    
    // 2. Trả PHÒNG CHÍNH về 'Sẵn sàng'
    $ketNoiDb->query("UPDATE phong SET trang_thai = 'Sẵn sàng' WHERE id = $phongChinhId");

    // 3. Trả CÁC PHÒNG PHỤ về 'Sẵn sàng' (Nếu đoàn > 1 phòng)
    // Logic: Tìm các phòng cùng loại đang ở trạng thái 'Đang ở' (trừ phòng chính) để trả
    if ($slPhong > 1) {
        $slPhu = $slPhong - 1;
        $sqlRevertSub = "UPDATE phong 
                         SET trang_thai = 'Sẵn sàng' 
                         WHERE loai_phong_id = $idLoaiPhong 
                         AND trang_thai = 'Đang ở' 
                         AND id != $phongChinhId 
                         LIMIT $slPhu";
        $ketNoiDb->query($sqlRevertSub);
    }

    echo "<script>
            alert('Thanh toán thành công! Đã trả $slPhong phòng về trạng thái Sẵn sàng.'); 
            window.location.href='danh_sach_dang_o.php';
          </script>";
    exit;
}
?>

<div class="container" style="max-width: 700px; margin-top: 30px;">
    <div class="card shadow">
        <div class="card-header bg-success text-white text-center">
            <h3 class="mb-0">HÓA ĐƠN THANH TOÁN (ĐOÀN)</h3>
        </div>
        <div class="card-body">
            
            <h4 class="text-center text-primary mb-4">
                <?php echo $donData['ten_loai']; ?> 
                <span class="badge badge-warning text-dark" style="font-size:0.6em; vertical-align:middle;">
                    SL: <?php echo $soLuong; ?> phòng
                </span>
            </h4>

            <table class="table table-bordered">
                <tr>
                    <th width="35%">Đại diện khách hàng:</th>
                    <td>
                        <b><?php echo htmlspecialchars($donData['ten_khach']); ?></b>
                    </td>
                </tr>
                <tr>
                    <th>Phòng chính:</th>
                    <td>Phòng số <b><?php echo $donData['so_phong']; ?></b></td>
                </tr>
                <tr>
                    <th>Chi tiết thời gian:</th>
                    <td>
                        Nhận: <?php echo date('d/m/Y', $ngayNhan); ?><br>
                        Trả: <b><?php echo date('d/m/Y', $ngayHienTai); ?></b> (Hôm nay)<br>
                        Thời gian tính: <b><?php echo $soNgayO; ?></b> ngày/đêm
                    </td>
                </tr>
                <tr>
                    <th>Chi tiết giá:</th>
                    <td>
                        Đơn giá: <?php echo number_format($giaPhong, 0, ',', '.'); ?> đ/đêm<br>
                        Số lượng: <b>x <?php echo $soLuong; ?></b> phòng
                    </td>
                </tr>
                <tr class="table-success" style="font-size: 1.3em;">
                    <th>TỔNG TIỀN PHẢI THU:</th>
                    <td class="font-weight-bold text-danger">
                        <?php echo number_format($tongTien, 0, ',', '.'); ?> VNĐ
                    </td>
                </tr>
            </table>

            <form method="POST">
                <input type="hidden" name="tong_tien" value="<?php echo $tongTien; ?>">
                <input type="hidden" name="phong_id" value="<?php echo $donData['phong_id']; ?>">
                <input type="hidden" name="loai_phong_id" value="<?php echo $donData['id_loai_phong']; ?>">
                <input type="hidden" name="so_luong" value="<?php echo $soLuong; ?>">
                
                <div class="text-center mt-4">
                    <a href="danh_sach_dang_o.php" class="btn btn-secondary mr-2">
                        <i class="fa fa-arrow-left"></i> Quay lại
                    </a>
                    
                    <button type="submit" class="btn btn-success btn-lg" 
                            onclick="return confirm('Xác nhận khách đã thanh toán đủ <?php echo number_format($tongTien); ?> đ?');">
                        <i class="fa fa-check-circle"></i> Xác nhận Thanh Toán
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footeradmin.php'; ?>