<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Danh Sách Người Đăng Ký Tài Liệu AI/ML</h4>
                    <a class="heading-elements-toggle"><i class="ft-ellipsis-h font-medium-3"></i></a>
                    <div class="heading-elements">
                        <button class="btn btn-primary btn-sm" onclick="reloadGrid()">
                            <i class="ft-refresh-cw"></i> Làm mới dữ liệu
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <!-- Kendo UI Grid Container -->
                        <div id="grid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.kendoGrid === 'undefined') {
            console.error('Kendo Grid library not loaded!');
            return;
        }

        jQuery("#grid").kendoGrid({
            columns: [
                {
                    title: "STT",
                    width: 55,
                    template: "#= renderRowIndex(data) #",
                    filterable: false,
                    sortable: false
                },
                {
                    field: "email",
                    title: "Email",
                    width: 180
                },
                {
                    field: "fullname",
                    title: "Họ và tên",
                    width: 140
                },
                {
                    field: "gender",
                    title: "Giới tính",
                    width: 90,
                    template: "#= renderGender(gender) #",
                    filterable: false
                },
                {
                    field: "birthday",
                    title: "Ngày sinh",
                    width: 100,
                    filterable: false
                },
                {
                    field: "occupation",
                    title: "Nghề nghiệp",
                    width: 110,
                    filterable: false
                },
                {
                    field: "address",
                    title: "Địa chỉ",
                    width: 140,
                    filterable: false
                },
                {
                    field: "is_verified",
                    title: "Xác minh",
                    width: 160,
                    template: "#= renderBadge(is_verified) #",
                    filterable: {
                        ui: verifiedFilterUI,
                        extra: false,
                        operators: {
                            string: {
                                eq: "Is equal to"
                            }
                        }
                    }
                },
                {
                    field: "is_email_read",
                    title: "Đọc email",
                    width: 170,
                    template: "#= renderBadge(email_read_at) #",
                    filterable: {
                        ui: emailReadFilterUI,
                        extra: false,
                        operators: {
                            string: {
                                eq: "Is equal to"
                            }
                        }
                    }
                },
                {
                    field: "is_downloaded",
                    title: "Download",
                    width: 150,
                    template: "#= renderBadge(downloaded_at) #",
                    filterable: {
                        ui: downloadedFilterUI,
                        extra: false,
                        operators: {
                            string: {
                                eq: "Is equal to"
                            }
                        }
                    }
                },
                {
                    field: "email_read_at",
                    title: "Thời gian đọc mail",
                    width: 150,
                    template: "#= formatDate(email_read_at) #",
                    filterable: false
                },
                {
                    field: "downloaded_at",
                    title: "Thời gian download",
                    width: 150,
                    template: "#= formatDate(downloaded_at) #",
                    filterable: false
                },
                {
                    title: "Hành động",
                    width: 110,
                    sortable: false,
                    filterable: false,
                    template: "#= renderActions(data) #"
                }
            ],
            dataSource: {
                transport: {
                    read: {
                        url: "<?php echo base_url('admin/registrations/readdb'); ?>",
                        contentType: "application/json",
                        type: "POST"
                    },
                    parameterMap: function (data) {
                        return kendo.stringify(data);
                    }
                },
                pageSize: 10,
                serverPaging: true,
                serverSorting: true,
                serverFiltering: true,
                sort: [
                    { field: "created_at", dir: "desc" }
                ],
                schema: {
                    data: "data",
                    total: "total"
                }
            },
            scrollable: true,
            sortable: true,
            pageable: {
                refresh: true,
                pageSizes: [5, 10, 20, 50],
                buttonCount: 5
            },
            filterable: {
                extra: false
            }
        });
    });

    function verifiedFilterUI(element) {
        element.kendoDropDownList({
            dataSource: [
                { text: "Đã xác minh email", value: 1 },
                { text: "Chưa xác minh email", value: 0 }
            ],
            dataTextField: "text",
            dataValueField: "value",
            optionLabel: "-- Tất cả --"
        });
    }

    function emailReadFilterUI(element) {
        element.kendoDropDownList({
            dataSource: [
                { text: "Đã đọc email download", value: 1 },
                { text: "Chưa đọc email download", value: 0 }
            ],
            dataTextField: "text",
            dataValueField: "value",
            optionLabel: "-- Tất cả --"
        });
    }

    function downloadedFilterUI(element) {
        element.kendoDropDownList({
            dataSource: [
                { text: "Đã download", value: 1 },
                { text: "Chưa download", value: 0 }
            ],
            dataTextField: "text",
            dataValueField: "value",
            optionLabel: "-- Tất cả --"
        });
    }

    function reloadGrid() {
        if (typeof jQuery !== 'undefined' && jQuery("#grid").data("kendoGrid")) {
            jQuery("#grid").data("kendoGrid").dataSource.read();
        }
    }

    function renderRowIndex(data) {
        var grid = jQuery("#grid").data("kendoGrid");
        if (!grid) return 1;
        var page = grid.dataSource.page();
        var pageSize = grid.dataSource.pageSize();
        var dataItems = grid.dataSource.data();
        var index = dataItems.indexOf(data);
        return (page - 1) * pageSize + index + 1;
    }

    function renderGender(gender) {
        if (gender === 'male') return 'Nam';
        if (gender === 'female') return 'Nữ';
        if (gender === 'other') return 'Khác';
        return gender || '-';
    }

    function renderBadge(val) {
        if (val == 1 || (val && val !== '0' && val !== 0)) {
            return '<span class="badge badge-success">✓</span>';
        }
        return '<span class="badge badge-secondary">✗</span>';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }

    function renderActions(data) {
        var html = '';
        if (data.is_verified == 0) {
            html += '<button class="btn btn-sm btn-warning" onclick="resendActivation(' + data.id + ')"><i class="ft-mail"></i> Gửi KH</button>';
        } else {
            html += '<button class="btn btn-sm btn-info" onclick="resendDownload(' + data.id + ')"><i class="ft-download"></i> Gửi DL</button>';
        }
        return html;
    }

    function resendActivation(id) {
        Swal.fire({
            title: 'Gửi lại email kích hoạt?',
            text: 'Hệ thống sẽ tạo token kích hoạt mới và gửi email.',
            type: 'question',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Gửi',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (result.value || result.isConfirmed) {
                Swal.fire({
                    title: 'Đang xử lý...',
                    text: 'Vui lòng chờ trong giây lát',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    onOpen: function () { if (Swal.showLoading) Swal.showLoading(); },
                    didOpen: function () { if (Swal.showLoading) Swal.showLoading(); }
                });

                jQuery.post('<?php echo base_url("admin/registrations/resend_activation"); ?>', { id: id }, function (response) {
                    Swal.fire({
                        type: response.success ? 'success' : 'error',
                        icon: response.success ? 'success' : 'error',
                        title: response.message
                    });
                    if (response.success) reloadGrid();
                }, 'json').fail(function() {
                    Swal.fire({
                        type: 'error',
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối đến server.'
                    });
                });
            }
        });
    }

    function resendDownload(id) {
        Swal.fire({
            title: 'Gửi lại email download?',
            text: 'Hệ thống sẽ gửi lại email download.',
            type: 'question',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Gửi',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (result.value || result.isConfirmed) {
                Swal.fire({
                    title: 'Đang xử lý...',
                    text: 'Vui lòng chờ trong giây lát',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    onOpen: function () { if (Swal.showLoading) Swal.showLoading(); },
                    didOpen: function () { if (Swal.showLoading) Swal.showLoading(); }
                });

                jQuery.post('<?php echo base_url("admin/registrations/resend_download"); ?>', { id: id }, function (response) {
                    Swal.fire({
                        type: response.success ? 'success' : 'error',
                        icon: response.success ? 'success' : 'error',
                        title: response.message
                    });
                    if (response.success) reloadGrid();
                }, 'json').fail(function() {
                    Swal.fire({
                        type: 'error',
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối đến server.'
                    });
                });
            }
        });
    }
</script>
