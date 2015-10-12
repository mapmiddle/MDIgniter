
<?php
    template_var($parse_fields, array());
?>

<table class="table table-bordered" style="margin-bottom: 0;">
    <tbody>
    <?php
        foreach ($parse_fields as $field) {
            ?>
                <tr>
                    <?php
                        if(array_key_exists('_REQUIRED', $field) && $field['_REQUIRED']) {
                            $required_text = '* ';
                        } else {
                            $required_text = '';
                        }
                    ?>
                    <td class="active text-right" style="width: 20%;">
                        <h4><?php echo $required_text.$field['_LABEL']; ?></h4>
                    </td>
                    <td>
                        <?php if (array_key_exists('_ERROR', $field)) { ?>
                            <div class="form-group has-error">
                        <?php } else { ?>
                            <div class="form-group">
                        <?php } ?>
                            <?php echo $field['_WIDGET']; ?>
                            <?php
                                if (array_key_exists('_ERROR', $field)) {
                                    ?><div class="bg-danger" style="padding:15px 15px 5px 15px; margin-top:10px;"><?php
                                        echo $field['_ERROR'];
                                    ?></div><?php
                                }
                            ?>
                        </div>
                    </td>
                </tr>
            <?php
        }
    ?>
    </tbody>
</table>