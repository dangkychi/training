<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-md-6 col-12">
                <h3 class="content-header-title">Đăng ký nhận tài liệu</h3>
            </div>
        </div>
        <div class="content-body">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Ứng dụng Trí tuệ nhân tạo AI và Machine Learning</h4>
                            <p class="text-muted">Điền thông tin bên dưới để nhận tài liệu miễn phí</p>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <form id="registerForm" action="javascript:void(0);" method="POST">
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="your-email@example.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="fullname">Họ tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="fullname" name="fullname"
                                            placeholder="Nguyễn Văn A" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender">Giới tính <span class="text-danger">*</span></label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="">-- Chọn --</option>
                                            <option value="male">Nam</option>
                                            <option value="female">Nữ</option>
                                            <option value="other">Khác</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="birthday">Ngày sinh <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="birthday" name="birthday" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="occupation">Nghề nghiệp <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="occupation" name="occupation"
                                            placeholder="Kỹ sư phần mềm" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Địa chỉ</label>
                                        <input type="text" class="form-control" id="address" name="address"
                                            placeholder="TP. Hồ Chí Minh">
                                    </div>

                                    <!-- Hidden GPS fields -->
                                    <input type="hidden" id="latitude" name="latitude">
                                    <input type="hidden" id="longitude" name="longitude">
                                    <p id="gps-status" class="text-muted small"></p>

                                    <div class="form-group text-center mt-2">
                                        <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                                            <i class="ft-send"></i> Đăng ký nhận tài liệu
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // GPS Geolocation
    (function () {
        if (navigator.geolocation) {
            document.getElementById('gps-status').textContent = 'Đang lấy vị trí GPS...';
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    document.getElementById('gps-status').textContent = 'Đã lấy vị trí GPS thành công.';
                },
                function (error) {
                    document.getElementById('gps-status').textContent = 'Không thể lấy vị trí GPS (đã bỏ qua).';
                },
                { timeout: 10000 }
            );
        }
    })();

    // Form submit
    document.addEventListener('DOMContentLoaded', function () {
        $('#registerForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#btnSubmit');
            $btn.prop('disabled', true).html('<i class="ft-loader spin"></i> Đang xử lý...');

            $.ajax({
                url: '<?php echo base_url("register/submit"); ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: response.message,
                            confirmButtonText: 'OK'
                        }).then(function () {
                            $('#registerForm')[0].reset();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: response.message,
                            confirmButtonText: 'Thử lại'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối đến server.',
                        confirmButtonText: 'Thử lại'
                    });
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="ft-send"></i> Đăng ký nhận tài liệu');
                }
            });
        });
    });
</script>