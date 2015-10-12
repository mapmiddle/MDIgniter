
<?php
    template_var($parse_dashboard, array());
?>

<?php
    foreach ($parse_dashboard as $value) {
        switch ($value['_TYPE']) {
            case 'GROUP':
                ?>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo $value['_LABEL']; ?></h3>
                        </div>
                        <div class="panel-body">
                            <?php
                                $this->load->view('templates/mdi_admin_dashboard_render_t', array(
                                    'parse_dashboard' => $value['_CHILDREN']
                                ));
                            ?>
                        </div>
                    </div>
                <?php
                break;
            case 'MODEL':
                ?>
                    <a href="<?php echo $value['_LINK']; ?>" style="text-decoration: none;">
                        <div class="block-model well btn-default">
                            <div>
                                <h1><?php echo $value['_MODEL']; ?></h1>
                                <h5>(<?php echo $value['_LABEL']; ?>)</h5>
                                <h5>Table : <?php echo $value['_TABLE']; ?></h5>
                                <h5>Total Records : <?php echo $value['_COUNT']; ?></h5>
                            </div>
                        </div>
                    </a>
                <?php
                break;
        }
    }
