<?php $this->mdi->statics_lazy('js', 'mdi/admin/admin-message.js'); ?>

<?php
    template_var($type, 'info');
    template_var($url, '');
    template_var($title, '');
    template_var($message, '');
    template_var($method, 'link');
    template_var($data, NULL);
?>

<div class="message container">
    <div class="alert alert-<?php echo $type; ?>" role="alert">
        <strong><?php echo $title; ?></strong> <?php echo $message; ?>
    </div>
    <div class="text-right">
        <?php if ($method == 'link'){ ?>
            <a href="<?php echo $url; ?>">
                <button type="submit" class="btn btn-lg btn-default">OK</button>
            </a>
        <?php } else { ?>
            <form id="message-form" action="<?php echo $url; ?>" method="<?php echo $method; ?>" >
                <div id="message-data" style="display: none;"><?php echo json_encode($data); ?></div>
                <button id="message-submit" type="submit" class="btn btn-lg btn-default">Confirm</button>
            </form>
        <?php }  ?>
    </div>
</div>