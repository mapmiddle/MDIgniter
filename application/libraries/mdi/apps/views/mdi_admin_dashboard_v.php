<?php $this->mdi->statics_lazy('js', 'mdi/admin/admin-dashboard.js'); ?>

<div class="dashboard container">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">Console</h3>
        </div>
        <div class="panel-body">
            <form id="console-form" class="form-horizontal" method="post" action="<?php echo admin_url('ajax/command'); ?>">
                <div class="form-group" style="margin-right:0; margin-left:0;">
                    <textarea id="console-display" class="col-sm-12 form-control" rows="5"></textarea>
                </div>
                <div class="form-group" style="margin-right:0; margin-left:0; margin-bottom: 0;">
                    <div class="col-sm-11" style="padding-left: 0;">
                        <input type="text" class="form-control" id="console-command-line" placeholder="Enter the command">
                    </div>
                    <button type="submit" class="btn btn-default col-sm-1">Enter</button>
                </div>
            </form>
        </div>
    </div>
    <?php
        $this->load->view('templates/mdi_admin_dashboard_render_t', array(
            'parse_dashboard' => $this->parse_dashboard
        ));
    ?>
</div>