<?php
// File: admin/so_do_phong.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit; 
}
include '../includes/ketnoidb.php';
include '../includes/headeradmin.php';

// =================================================================================
// 1. CẤU HÌNH THỜI GIAN TIMELINE (14 NGÀY)
// =================================================================================
$soNgayHienThi = 14;
$homNay = date('Y-m-d'); // Lấy ngày hiện tại

// Tạo danh sách các ngày cần hiển thị
$dsNgay = [];
for ($i = 0; $i < $soNgayHienThi; $i++) {
    $dsNgay[] = date('Y-m-d', strtotime($homNay . " + $i days"));
}

// Xác định phạm vi ngày để truy vấn SQL (Tối ưu hiệu suất)
$ngayBatDau = $dsNgay[0];
$ngayKetThuc = end($dsNgay);

// =================================================================================
// 2. XỬ LÝ BỘ LỌC
// =================================================================================
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$filterLoai = isset($_GET['loai_phong_id']) ? (int)$_GET['loai_phong_id'] : 0;

// Điều kiện lọc cho danh sách phòng
$wherePhong = "WHERE 1=1"; 
if ($keyword != '') {
    $wherePhong .= " AND p.so_phong LIKE '%$keyword%'";
}
if ($filterLoai > 0) {
    $wherePhong .= " AND p.loai_phong_id = $filterLoai";
}

// =================================================================================
// 3. TRUY VẤN DỮ LIỆU
// =================================================================================

// A. Lấy danh sách phòng (Trục dọc)
$sqlPhong = "SELECT p.id, p.so_phong, p.trang_thai, lp.ten_loai 
             FROM phong p 
             JOIN loai_phong lp ON p.loai_phong_id = lp.id 
             $wherePhong
             ORDER BY lp.ten_loai ASC, p.so_phong ASC";
$resPhong = $ketNoiDb->query($sqlPhong);

// B. Lấy dữ liệu đặt phòng (Data Mapping)
// Logic: Lấy các đơn có khoảng thời gian GIAO NHAU với khoảng thời gian hiển thị
$sqlBooking = "SELECT p.id as phong_id, dp.ngay_nhan, dp.ngay_tra, dp.ten_khach, dp.trang_thai 
               FROM dat_phong dp
               JOIN chi_tiet_dat_phong ct ON dp.id = ct.dat_phong_id
               JOIN phong p ON ct.phong_id = p.id
               WHERE dp.trang_thai NOT IN ('Đã hủy', 'Đã trả', 'Hủy do vắng mặt') 
               AND (dp.ngay_nhan <= '$ngayKetThuc' AND dp.ngay_tra >= '$ngayBatDau')";

$resBooking = $ketNoiDb->query($sqlBooking);

// C. Xử lý Mapping dữ liệu vào mảng 2 chiều: $dataMap[ID_PHONG][NGAY]
$dataMap = [];
if ($resBooking && $resBooking->num_rows > 0) {
    while ($row = $resBooking->fetch_assoc()) {
        $pid = $row['phong_id'];
        
        // Chuẩn hóa ngày để lặp (bỏ giờ phút)
        $start = new DateTime(date('Y-m-d', strtotime($row['ngay_nhan'])));
        $end   = new DateTime(date('Y-m-d', strtotime($row['ngay_tra'])));
        $end->modify('+1 day'); // Cộng 1 ngày để DatePeriod lấy đủ ngày cuối

        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        
        foreach ($period as $dt) {
            $currentDate = $dt->format('Y-m-d');
            
            // Chỉ lưu nếu ngày nằm trong danh sách hiển thị
            if (in_array($currentDate, $dsNgay)) {
                // Lưu thông tin vào mảng
                $dataMap[$pid][$currentDate] = [
                    'khach'      => $row['ten_khach'], 
                    'trang_thai' => $row['trang_thai'] 
                ];
            }
        }
    }
}

// Lấy danh sách loại phòng cho Dropdown lọc
$listLoai = $ketNoiDb->query("SELECT * FROM loai_phong");
?>

<style>
    .timeline-container {
        max-height: 75vh;
        overflow: auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        position: relative;
    }
    .tl-table {
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
        min-width: 1500px; /* Đảm bảo bảng không bị co nhỏ quá mức */
    }
    .tl-table th, .tl-table td {
        border-right: 1px solid #eee;
        border-bottom: 1px solid #eee;
        padding: 5px;
        text-align: center;
        height: 55px;
        box-sizing: border-box;
        vertical-align: middle;
    }
    
    /* Cố định cột tiêu đề và cột đầu tiên */
    .tl-table thead th { position: sticky; top: 0; background: #f8f9fa; z-index: 10; box-shadow: 0 2px 2px rgba(0,0,0,0.05); }
    .tl-table tbody th { position: sticky; left: 0; background: #fff; z-index: 5; border-right: 2px solid #ddd; min-width: 120px; text-align: left !important; padding-left: 15px; }
    .tl-table thead th:first-child { position: sticky; left: 0; top: 0; z-index: 20; background: #2c3e50; color: white; }

    /* Định dạng ô trạng thái */
    .cell-data {
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        width: 100%; height: 100%;
        border-radius: 4px; color: white; font-size: 0.8rem;
        cursor: pointer; overflow: hidden;
    }
    
    /* Màu sắc trạng thái ĐƠN HÀNG */
    .bg-booked { background-color: #3498db; } /* Xanh dương: Đã đặt */
    .bg-active { background-color: #e74c3c; } /* Đỏ: Đang ở */
    .bg-wait   { background-color: #f1c40f; color: #333; } /* Vàng: Chờ duyệt */
    
    /* Màu sắc trạng thái PHÒNG (Tĩnh) */
    .badge-status { padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; color: white; display: inline-block; }
    .st-maintenance { background-color: #e67e22; } /* Bảo trì */
    .st-cleaning    { background-color: #9b59b6; } /* Đang dọn */

    .is-today { background-color: #fff8e1 !important; border-bottom: 3px solid #f1c40f !important; }
</style>

<main class="container page-padding">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h1 class="tieu-de-muc" style="margin:0;">Sơ Đồ Timeline Phòng</h1>
        <div style="color: #666;">Hôm nay: <strong><?php echo date('d/m/Y'); ?></strong></div>
    </div>

    <form method="GET" style="background:white; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; gap:10px; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
        <input type="text" name="keyword" placeholder="Nhập số phòng..." value="<?php echo htmlspecialchars($keyword); ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
        <select name="loai_phong_id" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
            <option value="0">-- Tất cả loại --</option>
            <?php while($lp = $listLoai->fetch_assoc()): ?>
                <option value="<?php echo $lp['id']; ?>" <?php if($filterLoai == $lp['id']) echo 'selected'; ?>>
                    <?php echo $lp['ten_loai']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn-big-cta" style="padding:8px 15px; font-size:0.9rem;">Xem sơ đồ</button>
        <?php if($keyword || $filterLoai): ?>
            <a href="so_do_phong.php" style="color:red; margin-left:10px;">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <div class="timeline-container">
        <table class="tl-table">
            <thead>
                <tr>
                    <th style="width: 150px;">PHÒNG / NGÀY</th>
                    <?php foreach ($dsNgay as $ngay): ?>
                        <th class="<?php echo ($ngay == $homNay) ? 'is-today' : ''; ?>">
                            <?php echo date('d/m', strtotime($ngay)); ?><br>
                            <small style="font-weight:normal; color:#555;"><?php echo date('D', strtotime($ngay)); ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if($resPhong->num_rows > 0): ?>
                    <?php while ($p = $resPhong->fetch_assoc()): ?>
                        <tr>
                            <th>
                                <div style="font-weight:bold; color:#2c3e50; font-size:1.1rem;">P.<?php echo $p['so_phong']; ?></div>
                                <div style="font-size:0.8rem; color:#7f8c8d; font-weight:normal;"><?php echo $p['ten_loai']; ?></div>
                            </th>

                            <?php foreach ($dsNgay as $ngay): ?>
                                <td class="<?php echo ($ngay == $homNay) ? 'is-today' : ''; ?>">
                                    <?php
                                        // 1. ƯU TIÊN HIỂN THỊ ĐƠN ĐẶT PHÒNG
                                        if (isset($dataMap[$p['id']][$ngay])) {
                                            $info = $dataMap[$p['id']][$ngay];
                                            $tt   = $info['trang_thai'];
                                            
                                            // Xác định màu sắc dựa trên trạng thái đơn
                                            $cssClass = 'bg-booked'; // Mặc định xanh
                                            if (stripos($tt, 'ở') !== false || stripos($tt, 'check-in') !== false) {
                                                $cssClass = 'bg-active'; // Đỏ
                                            } elseif (stripos($tt, 'chờ') !== false) {
                                                $cssClass = 'bg-wait'; // Vàng
                                            }

                                            echo "<div class='cell-data $cssClass' title='Trạng thái: $tt'>";
                                            echo "<span>" . $info['khach'] . "</span>";
                                            echo "</div>";
                                        } 
                                        // 2. NẾU KHÔNG CÓ ĐƠN -> HIỂN THỊ TRẠNG THÁI PHÒNG (Chỉ hiện ở cột Hôm nay hoặc tương lai gần)
                                        elseif ($ngay == $homNay) {
                                            // Kiểm tra trạng thái tĩnh từ bảng 'phong'
                                            if ($p['trang_thai'] == 'Bảo trì') {
                                                echo "<span class='badge-status st-maintenance'><i class='fas fa-tools'></i> Bảo trì</span>";
                                            } elseif ($p['trang_thai'] == 'Đang dọn') {
                                                echo "<span class='badge-status st-cleaning'><i class='fas fa-broom'></i> Dọn</span>";
                                            } elseif ($p['trang_thai'] == 'Sẵn sàng') {
                                                // Có thể để trống hoặc hiện dấu tích
                                            }
                                        }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo $soNgayHienThi + 1; ?>" style="padding:30px; color:#999;">Không tìm thấy phòng nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px; display:flex; gap:20px; font-size:0.9rem; justify-content:center; flex-wrap:wrap;">
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px;" class="bg-booked"></span>&nbsp; Đã đặt (Giữ chỗ)</div>
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px;" class="bg-active"></span>&nbsp; Đang ở (Check-in)</div>
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px;" class="badge-status st-maintenance"></span>&nbsp; Phòng đang bảo trì</div>
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px;" class="badge-status st-cleaning"></span>&nbsp; Phòng đang dọn</div>
    </div>

</main>

<?php include '../includes/footeradmin.php'; ?>