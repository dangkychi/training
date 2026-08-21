<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body text-center py-3">
                                <?php if ($activate_status === 'success'): ?>
                                    <div class="mb-2">
                                        <i class="ft-check-circle text-success" style="font-size: 64px;"></i>
                                    </div>
                                    <h3 class="text-success">Xác minh thành công!</h3>
                                    <p class="text-muted">
                                        <?php echo $activate_message; ?>
                                    </p>
                                <?php elseif ($activate_status === 'already'): ?>
                                    <div class="mb-2">
                                        <i class="ft-info text-info" style="font-size: 64px;"></i>
                                    </div>
                                    <h3 class="text-info">Đã xác minh</h3>
                                    <p class="text-muted">
                                        <?php echo $activate_message; ?>
                                    </p>
                                <?php else: ?>
                                    <div class="mb-2">
                                        <i class="ft-x-circle text-danger" style="font-size: 64px;"></i>
                                    </div>
                                    <h3 class="text-danger">Lỗi!</h3>
                                    <p class="text-muted">
                                        <?php echo $activate_message; ?>
                                    </p>
                                <?php endif; ?>
                                <a href="<?php echo base_url(); ?>" class="btn btn-outline-primary mt-1">
                                    <i class="ft-home"></i> Về trang chủ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>