<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Hệ thống quản lý sửa chữa & bảo hành thiết bị – phần mềm</title>
    <link rel="shortcut icon" href="img/logo.png">

    <!-- Global Stylesheets -->
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i" rel="stylesheet">
    <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate/animate.min.css">
    <link rel="stylesheet" href="css/owl-carousel/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl-carousel/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/style.css">

    <!-- Core Stylesheets -->
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/shop.css"> 
    
  </head>

  <body id="page-top">

<!-- HEADER -->
<header>
  <nav class="navbar navbar-expand-lg navbar-light" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">
        <img src="img/logo.png" alt="logo" width="140">
      </a>

      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
          <li class="nav-item"><a class="nav-link" href="about.php">Giới thiệu</a></li>
          <li class="nav-item"><a class="nav-link" href="services.php">Dịch vụ</a></li>

          <?php if(isset($_SESSION['customer_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
              <li class="nav-item">
                  <a class="nav-link text-success" href="khachhang.php">
                      <i class="fa fa-user-circle"></i> <strong><?php echo htmlspecialchars($_SESSION['customer_name']); ?></strong>
                  </a>
              </li>
              
              <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" style="color: #ff9800;">
                      <i class="fa fa-bell"></i>
                  </a>
                  <div class="dropdown-menu dropdown-menu-right shadow" style="width:250px;">
                      <a class="dropdown-item" href="#">Thiết bị của bạn đã sửa xong</a>
                      <a class="dropdown-item text-muted small" href="#">Xem tất cả thông báo...</a>
                  </div>
              </li>

              <li class="nav-item">
                  <a class="nav-link text-danger" href="../backend/api/logout_customer.php">
                      <i class="fa fa-sign-out"></i> Đăng xuất
                  </a>
              </li>

          <?php else: ?>
              <li class="nav-item">
                  <?php if(basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                      <a href="#" class="nav-link smooth-scroll" data-toggle="modal" data-target="#login-modal">Đăng nhập</a>
                  <?php else: ?>
                      <a class="nav-link smooth-scroll" href="index.php?show_login=true">Đăng nhập</a>
                  <?php endif; ?>
              </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>

 
<!--HOME-P-->
    <div id="home-p" class="home-p pages-head2 text-center">
      <div class="container">
        <h1 class="wow fadeInUp" data-wow-delay="0.1s">Hệ thống quản lý sửa chữa và bảo hành thiết bị – phần mềm</h1>
        <p>Dịch vụ và chức năng hệ thống</p>
      </div><!--/end container-->
    </div> 

<!--SHOP-P1--> 
<section id="shop-p1" class="shop-p1">
  <div class="container">
    <div class="row title-bar">
      <div class="col-lg-3">
        <div class="shop-p1-title">  
          <h3>Các gói dịch vụ</h3>
          <div class="heading-border-light"></div> 
        </div>
        <div class="list-group">
          <a href="#" class="list-group-item active">Tất cả</a>
          <a href="#" class="list-group-item">Bảo Hành</a>
          <a href="#" class="list-group-item">Sửa chữa</a>
        </div>
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Tìm kiếm...">
          <div class="input-group-addon"><i class="fa fa-search"></i></div>
        </div>
      </div> 

<div class="col-lg-8"> 
  <div class="row">

    <!-- CARD 1 -->
    <div class="col-lg-4 col-md-6 mb-4">
      <div class="card idt-history-card h-100 d-flex flex-column">
        
        <div class="idt-product-img">
            <img src="img/shop/scan.png" alt="Zeta">
        </div>

        <div class="card-body text-center d-flex flex-column flex-grow-1">
            
            <div class="card-title idt-compact-row">
                <a href="#">Máy quét tài liệu Zeta</a>
            </div>

            <div class="cart-icon text-center mt-auto">
                <a href="#" class="status-link">Sửa chữa ngay</a>
            </div>

        </div>
      </div>
    </div>

    <!-- CARD 2 -->
    <div class="col-lg-4 col-md-6 mb-4">
      <div class="card idt-history-card h-100 d-flex flex-column">
        
        <div class="idt-product-img">
            <img src="img/shop/EM.jpg" alt="EM">
        </div>

        <div class="card-body text-center d-flex flex-column flex-grow-1">
            
            <div class="card-title idt-compact-row">
                <a href="#">Thiết bị tự động hóa EM-barcode</a>
            </div>

            <div class="cart-icon text-center mt-auto">
                <a href="#" class="status-link">Bảo hành ngay</a>
            </div>

        </div>
      </div>
    </div>

  </div>
</div>

        </div>
      </div> 
    </div> 
  </div>
</section>

<!--BUSINESS-GROWTH-P1-->
    <section id="business-growth-p1" class="business-growth-p1 bg-gray">
      <div class="container">
        <div class="row title-bar">
          <div class="col-md-12">
            <h1 class="wow fadeInUp">Chúng tôi cam kết đồng hành cùng doanh nghiệp</h1>
            <div class="heading-border"></div>
            <p class="wow fadeInUp" data-wow-delay="0.4s">Hệ thống được xây dựng nhằm hỗ trợ doanh nghiệp quản lý toàn bộ quy trình sửa chữa,
          bảo hành thiết bị và dịch vụ phần mềm một cách hiệu quả, minh bạch và chính xác.
          Giúp nâng cao hiệu suất làm việc, giảm sai sót và tối ưu chi phí vận hành.
          </div>
        </div>
        <div class="row wow animated fadeInUp" data-wow-duration="1s" data-wow-delay="0.5s">
          <div class="col-md-3 col-sm-6 service-padding">
              <div class="service-item">
                  <div class="service-item-icon"> <i class="fa fa-paint-brush fa-3x"></i>
                  </div>
                  <div class="service-item-title">
                      <h3>Quản lý thiết bị</h3>
                  </div>
                  <div class="service-item-desc">
                      <p>Lưu trữ thông tin chi tiết thiết bị, tình trạng sử dụng, lịch sử sửa chữa và bảo hành.</p> 
                      <div class="content-title-underline-light"></div> 
                  </div>
              </div>
          </div>
          <div class="col-md-3 col-sm-6 service-padding">
              <div class="service-item">
                  <div class="service-item-icon"> <i class="fa fa-laptop fa-3x"></i>
                  </div>
                  <div class="service-item-title">
                      <h3>Theo dõi sửa chữa</h3>
                  </div>
                  <div class="service-item-desc">
                      <p>Theo dõi tiến độ sửa chữa theo từng giai đoạn, phân công kỹ thuật viên rõ ràng.</p>
                      <div class="content-title-underline-light"></div> 
                  </div>
              </div>
          </div>
          <div class="col-md-3 col-sm-6 service-padding">
              <div class="service-item">
                  <div class="service-item-icon"> <i class="fa fa-table fa-3x"></i>
                  </div>
                  <div class="service-item-title">
                      <h3>Quản lý bảo hành</h3>
                  </div>
                  <div class="service-item-desc">
                      <p>Kiểm soát thời hạn bảo hành, tự động cảnh báo khi sắp hoặc đã hết hạn bảo hành.</p>
                      <div class="content-title-underline-light"></div> 
                  </div>
              </div>
          </div>
          <div class="col-md-3 col-sm-6 service-padding">
              <div class="service-item right-bord">
                  <div class="service-item-icon"> <i class="fa fa-search fa-3x"></i>
                  </div>
                  <div class="service-item-title">
                      <h3>Phân quyền và báo cáo</h3>
                  </div>
                  <div class="service-item-desc">
                      <p>Phân quyền theo vai trò (trưởng ban, nhân viên), thống kê và báo cáo tổng hợp.</p>
                      <div class="content-title-underline-light"></div> 
                  </div>
              </div>
          </div> 
        </div>
      </div>  
    </section>     

<!--FOOTER--> 
    <footer> 
        <div id="footer-s1" class="footer-s1">
          <div class="footer">
            <div class="container-fluid" style="padding-right:80px;">
              <div class="row" style="margin:0; justify-content:flex-end;">
                <!-- About Us -->
                <div class="col-md-3 col-sm-6" style="margin-right:80px;">
                  <div><img src="img/logo.png" alt="" class="img-fluid"></div>
                  <ul class="list-unstyled comp-desc-f">
                     <li><p>Chúng tôi cung cấp dịch vụ bảo hành, sửa chữa và bảo trì
                    chuyên nghiệp, nhanh chóng và uy tín cho khách hàng.</p></li> 
                  </ul><br> 
                </div>
                <!-- End About Us -->

                <!-- Recent News -->
                <div class="col-md-3 col-sm-6" style="margin-right:80px;">
                  <div class="heading-footer"><h2>Số 3/36 Trần Điền - Phường Phương Liệt - Hà Nội</h2></div>
                  <ul class="list-unstyled link-list">
                    <li><a class="fa fa-envelope" href="index.php"> contact@idtvietnam.vn</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> 0243.2222.720</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> Hotline: 0904.288.822</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> VPHN: 0246.291.1401/0246.326.1898</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> VPMN: 0282.229.5501/0938.651.659</a><i class="fa fa-angle-right"></i></li> 
                  </ul>
                </div>
                <!-- End Recent list -->

                <!-- Latest Tweets -->
                <div class="col-md-3 col-sm-6" style="margin-right:80px;">
                  <div class="heading-footer"><h2>Hỗ trợ kỹ thuật</h2></div>
                  <address class="address-details-f">
                    tech.support@idtvietnam.vn<br>
                    Miền Bắc - Miền Trung: 024.62.911.224<br>
                    Miền Nam: 0938.651.659<br>
                  </address>  
                  <ul class="list-inline social-icon-f top-data">
                    <li><a href="#" target="_empty"><i class="fa top-social fa-facebook"></i></a></li>
                    <li><a href="#" target="_empty"><i class="fa top-social fa-twitter"></i></a></li>
                    <li><a href="#" target="_empty"><i class="fa top-social fa-google-plus"></i></a></li> 
                  </ul>
                </div>
                <!-- End Latest Tweets -->
              </div>
            </div><!--/container -->
          </div> 
        </div>

        <div id="footer-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div id="footer-copyrights">
                            <p>&copy; 2026 IDT Design. All rights reserved. <a href="#">Chính sách bảo mật</a> <a href="#">Điều khoản bảo mật</a></p>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
        <a href="#" id="back-to-top" class="btn btn-sm btn-green btn-back-to-top smooth-scrolls hidden-sm hidden-xs" title="home" role="button">
            <i class="fa fa-angle-up"></i>
        </a>
    </footer>

    <!--Global JavaScript -->
    <script src="js/jquery/jquery.min.js"></script>
    <script src="js/popper/popper.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script src="js/wow/wow.min.js"></script>
    <script src="js/owl-carousel/owl.carousel.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="js/jquery-easing/jquery.easing.min.js"></script> 
    
    <script src="js/custom.js"></script> 
  </body>

</html>
