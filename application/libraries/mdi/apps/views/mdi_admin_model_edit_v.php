<?php $this->mdi->statics_lazy('js', 'mdi/bootstrap-datetimepicker/moment.js'); ?>
<?php $this->mdi->statics_lazy('js', 'mdi/bootstrap-datetimepicker/bootstrap-datetimepicker.js'); ?>
<?php $this->mdi->statics_lazy('js', 'mdi/bootstrap-mdi/bootstrap-mdi-modelselector.js'); ?>
<?php $this->mdi->statics_lazy('js', 'mdi/admin/admin-model-edit.js'); ?>

<form action="<?php echo current_url(); ?>" method="post" accept-charset="utf-8">
    <div class="model-edit container">
        <div style="margin-bottom: 20px;">
            <h1><?php echo $this->parse_model['_MODEL']; ?>
                <small>(<?php echo $this->parse_model['_LABEL']; ?>) (Table : <?php echo $this->parse_model['_TABLE']; ?>)</small>
            </h1>
        </div>
        <?php foreach ($this->parse_model['_FIELDS'] as $field) { ?>
            <?php if (!empty($field['_CHILDREN'])) {?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo $field['_LABEL']; ?></h3>
                    </div>
                    <div class="panel-body">
                        <?php
                            $this->load->view('templates/mdi_admin_model_edit_table_render_t', array(
                                'parse_fields' => $field['_CHILDREN']
                            ));
                        ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <nav class="navbar navbar-inverse navbar-fixed-bottom">
        <div class="container">
            <div class="navbar-right">
                <div class="item">
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </div>
        </div>
    </nav>
</form>