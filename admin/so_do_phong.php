<?php
// File: admin/so_do_phong.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
include '../includes/ketnoidb.php';
include '../includes/headeradmin.php';

// =================================================================================
// 1. CẤU HÌNH THỜI GIAN (HIỂN THỊ 14 NGÀY)
// =================================================================================
$soNgayHienThi = 14;
$homNay = date('Y-m-d');
$ngayBatDau = $homNay; 
$dsNgay = [];
for ($i = 0; $i < $soNgayHienThi; $i++) {
    $dsNgay[] = date('Y-m-d', strtotime($ngayBatDau . " + $i days"));
}
$ngayKetThuc = end($dsNgay);

// =================================================================================
// 2. XỬ LÝ BỘ LỌC TÌM KIẾM
// =================================================================================
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$filterLoai = isset($_GET['loai_phong_id']) ? (int)$_GET['loai_phong_id'] : 0;

// Tạo điều kiện lọc cho danh sách phòng
$whereClause = "WHERE 1=1"; 
if ($keyword != '') {
    $whereClause .= " AND p.so_phong LIKE '%$keyword%'";
}
if ($filterLoai > 0) {
    $whereClause .= " AND p.loai_phong_id = $filterLoai";
}

// =================================================================================
// 3. LẤY DANH SÁCH PHÒNG (TRỤC DỌC)
// =================================================================================
$sqlPhong = "SELECT p.id, p.so_phong, p.trang_thai, lp.ten_loai 
             FROM phong p 
             JOIN loai_phong lp ON p.loai_phong_id = lp.id 
             $whereClause
             ORDER BY lp.ten_loai ASC, p.so_phong ASC";
$resPhong = $ketNoiDb->query($sqlPhong);

// =================================================================================
// 4. LẤY DỮ LIỆU ĐẶT PHÒNG ĐỂ LẤP VÀO Ô (DATA MAPPING)
// =================================================================================
// Lấy từ bảng CHI TIẾT để đảm bảo chính xác từng phòng
$sqlBooking = "SELECT p.id as phong_id, dp.ngay_nhan, dp.ngay_tra, dp.ten_khach, dp.trang_thai 
               FROM dat_phong dp
               JOIN chi_tiet_dat_phong ct ON dp.id = ct.dat_phong_id
               JOIN phong p ON ct.phong_id = p.id
               WHERE (dp.ngay_nhan <= '$ngayKetThuc' AND dp.ngay_tra >= '$ngayBatDau')
               AND dp.trang_thai IN ('Đã duyệt', 'Đang ở')";

$resBooking = $ketNoiDb->query($sqlBooking);

// Chuyển dữ liệu vào mảng 2 chiều: $dataMap[ID_PHONG][NGAY] = Thông tin
$dataMap = [];
if ($resBooking) {
    while ($row = $resBooking->fetch_assoc()) {
        $pid = $row['phong_id'];
        // Tạo khoảng ngày từ NgayNhan đến NgayTra
        $period = new DatePeriod(
            new DateTime($row['ngay_nhan']), 
            new DateInterval('P1D'), 
            (new DateTime($row['ngay_tra']))->modify('+1 day')
        );
        
        foreach ($period as $dt) {
            $d = $dt->format('Y-m-d');
            if (in_array($d, $dsNgay)) {
                $dataMap[$pid][$d] = [
                    'khach' => $row['ten_khach'], 
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
    .timeline-wrapper {
        max-height: 75vh; /* Chiều cao tối đa của bảng */
        overflow: auto;   /* Bật thanh cuộn 2 chiều */
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #ddd;
        position: relative;
    }

    .tl-table {
        width: 100%;
        border-collapse: separate; /* Bắt buộc cho sticky */
        border-spacing: 0;
        min-width: 1800px; /* Độ rộng tối thiểu để không bị co */
    }

    .tl-table th, .tl-table td {
        border-right: 1px solid #eee;
        border-bottom: 1px solid #eee;
        padding: 8px;
        text-align: center;
        font-size: 0.85rem;
        box-sizing: border-box;
        height: 60px; /* Chiều cao cố định cho ô */
    }

    /* STICKY HEADER (NGÀY) */
    .tl-table thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        color: #555;
    }

    /* STICKY COLUMN (PHÒNG) */
    .tl-table tbody th, .col-room-header {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 5;
        border-right: 2px solid #ddd;
        min-width: 100px;
        max-width: 160px;
        text-align: left !important;
        padding-left: 15px !important;
        box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1);
    }

    /* GIAO ĐIỂM (GÓC TRÁI TRÊN) */
    .tl-table thead th:first-child {
        position: sticky;
        left: 0;
        top: 0;
        z-index: 20;
        background: #2c3e50;
        color: white;
        border-right: 2px solid #ddd;
    }

    /* TRẠNG THÁI MÀU SẮC */
    .status-booked { background-color: #3498db; color: white; border-radius: 4px; font-size: 0.8em; padding: 4px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: help; }
    .status-active { background-color: #e74c3c; color: white; border-radius: 4px; font-size: 0.8em; padding: 4px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: help; }
    
    .status-fixing { background-color: #f39c12; color: white; font-size: 0.8em; padding: 4px 8px; border-radius: 15px; font-weight: bold; display: inline-block; }
    .status-cleaning { background-color: #9b59b6; color: white; font-size: 0.8em; padding: 4px 8px; border-radius: 15px; font-weight: bold; display: inline-block; }

    .is-today { background-color: #fff8e1 !important; border-bottom: 3px solid #ffc107 !important; }
    
    .filter-bar { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
    .form-control { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; outline: none; }
    .btn-search { background: #2c3e50; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
    .btn-search:hover { background: #34495e; }
</style>

<main class="container page-padding">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h1 class="tieu-de-muc" style="margin:0;">Sơ Đồ Timeline Phòng</h1>
        <div style="font-size:0.9em; color:#666;">Hôm nay: <b><?php echo date('d/m/Y'); ?></b></div>
    </div>

    <form method="GET" class="filter-bar">
        <div style="display:flex; align-items:center; gap:10px;">
            <label>Số phòng:</label>
            <input type="text" name="keyword" class="form-control" placeholder="Nhập số (VD: 101)" value="<?php echo htmlspecialchars($keyword); ?>">
        </div>
        
        <div style="display:flex; align-items:center; gap:10px;">
            <label>Loại phòng:</label>
            <select name="loai_phong_id" class="form-control">
                <option value="0">-- Tất cả --</option>
                <?php while($lp = $listLoai->fetch_assoc()): ?>
                    <option value="<?php echo $lp['id']; ?>" <?php if($filterLoai == $lp['id']) echo 'selected'; ?>>
                        <?php echo $lp['ten_loai']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn-search"><i class="fas fa-search"></i> Tìm kiếm</button>
        
        <?php if($keyword || $filterLoai): ?>
            <a href="so_do_phong.php" style="color:#e74c3c; text-decoration:underline; font-size:0.9em; margin-left:10px;">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <div class="timeline-wrapper">
        <table class="tl-table">
            <thead>
                <tr>
                    <th class="col-room-header">PHÒNG / NGÀY</th>
                    <?php foreach ($dsNgay as $ngay): ?>
                        <th class="<?php echo ($ngay == $homNay) ? 'is-today' : ''; ?>">
                            <?php 
                                echo date('d/m', strtotime($ngay)); 
                                echo "<br><span style='font-size:0.8em; font-weight:normal; color:#666;'>" . date('D', strtotime($ngay)) . "</span>";
                            ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if($resPhong->num_rows > 0): ?>
                    <?php while ($p = $resPhong->fetch_assoc()): ?>
                        <tr>
                            <th class="col-room-header">
                                <strong style="font-size:1.1em; color:#2c3e50;">P.<?php echo $p['so_phong']; ?></strong><br>
                                <span style="font-size:0.75em; color:#7f8c8d; font-weight:normal;"><?php echo $p['ten_loai']; ?></span>
                            </th>

                            <?php foreach ($dsNgay as $ngay): ?>
                                <td class="<?php echo ($ngay == $homNay) ? 'is-today' : ''; ?>">
                                    <?php
                                        // 1. ƯU TIÊN: HIỂN THỊ ĐẶT PHÒNG (Khách)
                                        if (isset($dataMap[$p['id']][$ngay])) {
                                            $info = $dataMap[$p['id']][$ngay];
                                            // Phân biệt màu sắc
                                            $class = ($info['trang_thai'] == 'Đang ở') ? 'status-active' : 'status-booked';
                                            // Hiển thị tên khách
                                            echo "<span class='$class' title='{$info['khach']}'>{$info['khach']}</span>";
                                        } 
                                        
                                        // 2. NẾU TRỐNG & LÀ HÔM NAY: HIỂN THỊ TRẠNG THÁI PHÒNG (Bảo trì/Dọn)
                                        else if ($ngay == $homNay) {
                                            // Lấy trạng thái thực tế từ bảng phong
                                            if ($p['trang_thai'] == 'Bảo trì') {
                                                echo "<span class='status-fixing'><i class='fas fa-tools'></i> Bảo trì</span>";
                                            } 
                                            elseif ($p['trang_thai'] == 'Đang dọn') {
                                                echo "<span class='status-cleaning'><i class='fas fa-broom'></i> Dọn</span>";
                                            }
                                            // Sẵn sàng thì để trống cho thoáng
                                        }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <th class="col-room-header">-</th>
                        <td colspan="<?php echo $soNgayHienThi; ?>" style="text-align:center; padding:50px; color:#999;">
                            Không tìm thấy phòng nào phù hợp.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top:20px; display:flex; gap:25px; font-size:0.9em; justify-content:center; padding:10px; background:#f9f9f9; border-radius:5px;">
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px; background:#3498db; margin-right:8px; border-radius:3px;"></span> Đã đặt (Giữ chỗ)</div>
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px; background:#e74c3c; margin-right:8px; border-radius:3px;"></span> Đang ở (Có khách)</div>
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px; background:#f39c12; margin-right:8px; border-radius:3px;"></span> Đang bảo trì</div>
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px; background:#9b59b6; margin-right:8px; border-radius:3px;"></span> Đang dọn dẹp</div>
        <div style="display:flex; align-items:center;"><span style="width:15px; height:15px; background:#fff8e1; border:1px solid #ffc107; margin-right:8px; border-radius:3px;"></span> Cột hôm nay</div>
    </div>

</main>

<?php include '../includes/footeradmin.php'; ?>