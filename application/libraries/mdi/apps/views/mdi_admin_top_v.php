<?php $this->load->helper('html'); echo doctype('html5'); ?>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->mdi->statics('mdi/admin/admin.css'); ?>
    <?php $this->mdi->statics('mdi/bootstrap/css/bootstrap.min.css'); ?>
    <?php $this->mdi->statics_flush('css'); ?>
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <?php
            if (empty($this->navs)) {
        ?>
                <a class="navbar-brand" href="<?php echo admin_url(); ?>">
                    <?php echo $this->mdi->config('project'); ?>
                </a>
        <?php
            } else {
                foreach($this->navs as $key => $value) {
                    ?>
                        <a class="navbar-brand" href="<?php echo $value; ?>">
                            <?php echo $key; ?>
                        </a>
                    <?php
                    end($this->navs);
                    if ($key !== key($this->navs)) {
                        ?>
                            <span class="navbar-brand">
                                >
                            </span>
                        <?php
                    }
                }
            }
        ?>
        <?php if ($this->user) { ?>
            <div class="navbar-right">
                <div class="item">
                    <span class="btn btn-sm btn-primary"><?php echo $this->user->email; ?></span>
                </div>
                <div class="item">
                    <a href="<?php echo admin_url('logout'); ?>?redirect_url=<?php echo current_url(); ?>">
                        <span class="btn btn-sm btn-danger">Logout</span>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</nav>
