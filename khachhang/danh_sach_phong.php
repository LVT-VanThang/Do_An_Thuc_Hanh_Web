<?php
include '../includes/ketnoidb.php';
$tieuDeTrang = "Tìm kiếm phòng - Khách sạn ABC";
include '../includes/headerkhachhang.php';

// --- XỬ LÝ LỌC DỮ LIỆU ---
$sql = "SELECT * FROM loai_phong WHERE 1=1";

if (isset($_GET['suc_chua']) && $_GET['suc_chua'] != 'all') {
    $sucChua = (int)$_GET['suc_chua'];
    $sql .= " AND suc_chua >= $sucChua";
}
if (isset($_GET['so_giuong']) && $_GET['so_giuong'] != 'all') {
    $soGiuong = (int)$_GET['so_giuong'];
    $sql .= " AND so_giuong >= $soGiuong";
}
if (isset($_GET['view']) && $_GET['view'] != 'all') {
    $view = $ketNoiDb->real_escape_string($_GET['view']);
    $sql .= " AND huong_nhin LIKE '%$view%'";
}
if (isset($_GET['muc_gia']) && $_GET['muc_gia'] != 'all') {
    $mucGia = $_GET['muc_gia'];
    if ($mucGia == 'duoi-2tr') $sql .= " AND gia_tien < 2000000";
    elseif ($mucGia == '2tr-3tr') $sql .= " AND gia_tien BETWEEN 2000000 AND 3000000";
    elseif ($mucGia == 'tren-3tr') $sql .= " AND gia_tien > 3000000";
}

$ketQua = $ketNoiDb->query($sql);
?>

<main>
    <div class="thanh-tim-kiem-sticky">
        <div class="container">
            <form action="danh_sach_phong.php" method="GET" class="form-tim-kiem-ngang">
                
                <div class="input-item">
                    <label>Số người:</label>
                    <select name="suc_chua">
                        <option value="all">Tất cả</option>
                        <option value="1" <?php if(isset($_GET['suc_chua']) && $_GET['suc_chua'] == 1) echo 'selected'; ?>>1+</option>
                        <option value="2" <?php if(isset($_GET['suc_chua']) && $_GET['suc_chua'] == 2) echo 'selected'; ?>>2+</option>
                        <option value="4" <?php if(isset($_GET['suc_chua']) && $_GET['suc_chua'] == 4) echo 'selected'; ?>>4+</option>
                    </select>
                </div>

                <div class="input-item">
                    <label>Giường:</label>
                    <select name="so_giuong">
                        <option value="all">Tất cả</option>
                        <option value="1" <?php if(isset($_GET['so_giuong']) && $_GET['so_giuong'] == 1) echo 'selected'; ?>>1</option>
                        <option value="2" <?php if(isset($_GET['so_giuong']) && $_GET['so_giuong'] == 2) echo 'selected'; ?>>2</option>
                    </select>
                </div>

                <div class="input-item">
                    <label>View:</label>
                    <select name="view">
                        <option value="all">Tất cả</option>
                        <option value="Biển" <?php if(isset($_GET['view']) && $_GET['view'] == 'Biển') echo 'selected'; ?>>Biển</option>
                        <option value="Thành phố" <?php if(isset($_GET['view']) && $_GET['view'] == 'Thành phố') echo 'selected'; ?>>Phố</option>
                        <option value="Sân vườn" <?php if(isset($_GET['view']) && $_GET['view'] == 'Sân vườn') echo 'selected'; ?>>Vườn</option>
                    </select>
                </div>

                <div class="input-item">
                    <label>Giá:</label>
                    <select name="muc_gia">
                        <option value="all">Tất cả</option>
                        <option value="duoi-2tr" <?php if(isset($_GET['muc_gia']) && $_GET['muc_gia'] == 'duoi-2tr') echo 'selected'; ?>>&lt; 2tr</option>
                        <option value="2tr-3tr" <?php if(isset($_GET['muc_gia']) && $_GET['muc_gia'] == '2tr-3tr') echo 'selected'; ?>>2-3tr</option>
                        <option value="tren-3tr" <?php if(isset($_GET['muc_gia']) && $_GET['muc_gia'] == 'tren-3tr') echo 'selected'; ?>>&gt; 3tr</option>
                    </select>
                </div>

                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Lọc</button>
                <a href="danh_sach_phong.php" class="btn-reset" title="Xóa bộ lọc"><i class="fas fa-sync-alt"></i></a>
            </form>
        </div>
    </div>

    <div class="container page-padding" style="margin-top: 20px;">
        <h2 style="margin-bottom: 20px; text-align: center;">Danh sách loại phòng phù hợp</h2>
        
        <div class="luoi-phong">
            <?php
            if ($ketQua->num_rows > 0) {
                while($dong = $ketQua->fetch_assoc()) {
                    if (!empty($dong['anh_dai_dien'])) {
                        $anhBase64 = base64_encode($dong['anh_dai_dien']);
                        $nguonAnh = 'data:image/jpeg;base64,' . $anhBase64;
                    } else { $nguonAnh = 'images/no-image.jpg'; }
            ?>
                    <div class="the-phong">
                        <div class="khung-anh">
                            <img src="<?php echo $nguonAnh; ?>" alt="<?php echo $dong['ten_loai']; ?>">
                        </div>
                        <div class="noi-dung-phong">
                            <h3><?php echo $dong['ten_loai']; ?></h3>
                            <div class="thong-tin-phu" style="color:#666; font-size:0.9rem; margin-bottom:10px;">
                                <span><i class="fas fa-user"></i> <?php echo $dong['suc_chua']; ?></span> | 
                                <span><i class="fas fa-bed"></i> <?php echo $dong['so_giuong']; ?></span> | 
                                <span><i class="fas fa-eye"></i> <?php echo $dong['huong_nhin']; ?></span>
                            </div>
                            <p class="gia-phong">
                                <?php echo number_format($dong['gia_tien'], 0, ',', '.'); ?> VNĐ
                            </p>
                            <a href="chi_tiet_phong.php?id=<?php echo $dong['id']; ?>" class="nut-xem-phong">Xem Chi Tiết</a>
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

<?php 
$ketNoiDb->close();
include '../includes/footerkhachhang.php'; 
?>