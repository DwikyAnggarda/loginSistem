<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-3 text-gray-800"><?= $title; ?></h1>

    <div class="row">
        <div class="col-lg-6">

            <h5>Role : <?= $role['role']; ?></h5>

            <!-- Flashdata / Notification -->
            <?= $this->session->flashdata('message'); ?>

            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Role</th>
                        <th scope="col">Access</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $x = 1; ?>
                    <?php foreach ($menu as $m) : ?>
                        <tr>
                            <th scope="row"><?= $x; ?></th>
                            <td><?= $m['menu']; ?></td>
                            <td>
                                <div class="form-group form-check">
                                    <!-- Cek akses menggunakan helper Check_Access -->
                                    <input type="checkbox" class="form-check-input" id="exampleCheck1" <?= check_access($role['id'], $m['id']); ?> data-role="<?= $role['id']; ?>" data-menu="<?= $m['id']; ?>">
                                </div>
                            </td>
                        </tr>
                        <?php $x++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
    <a href="<?= base_url('admin/role') ?>" class="btn btn-primary">Back</a>

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->