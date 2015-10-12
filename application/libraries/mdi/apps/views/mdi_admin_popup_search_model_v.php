<?php
$this->mdi->statics_lazy('js', 'mdi/admin/admin-popup-search-model.js');

$object = $this->parse_popup_search_model['_OBJECT'];
$count_group = 0;
$count = 0;
$field_group = array();

foreach ($object->default_fields as $field => $attribute) {
    if (!isset($field_group[$count_group])) {
        $field_group[$count_group] = array();
    }

    $field_group[$count_group][$field] = $attribute;
    $count += 1;

    if ($count%4 == 0) {
        $count_group += 1;
    }
}
?>

<div class="popup-search-model container">
    <div id="pannel-input" class="panel panel-primary" style="margin-bottom:0; border-radius: 0;">
        <div class="panel-heading" style="border-radius: 0;">
            <h3 class="panel-title">Search Options</h3>
        </div>
        <div class="panel-body" style="padding: 0;">
            <?php
            foreach ($field_group as $index => $group) {
                $col_size = 12/count($field_group[$index]);
            ?>
            <table class="table table-bordered" style="margin-bottom: 0;">
                <tbody>
                    <tr>
                        <?php foreach ($group as $field => $attribute) { ?>
                            <td class="active text-center col-xs-<?php echo $col_size; ?>" style="font-size: 12px; padding: 4px;">
                                    <?php
                                    if (array_key_exists('label', $attribute)) {
                                        echo $attribute['label'];
                                    } else {
                                        echo $field;
                                    }
                                    ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <?php foreach ($group as $field => $attribute) { ?>
                            <td class="text-center col-xs-<?php echo $col_size; ?>">
                                <input class="form-control input-sm" type="text" name="<?php echo $field; ?>">
                            </td>
                        <?php } ?>
                    </tr>
                </tbody>
            </table>
            <?php } ?>
            <button type="button" id="search-btn" class="btn btn-sm btn-default" style="width: 100%; border-radius: 0;"
                data-model="<?php echo $this->parse_popup_search_model['_MODEL']; ?>"
                data-url="<?php echo admin_url('ajax/search_model'); ?>">Search</button>
        </div>
    </div>

    <div id="pannel-result" class="panel panel-primary" style="margin-bottom:0; border-radius: 0;">
        <div class="panel-heading" style="border-radius: 0;">
            <h3 class="panel-title">Result</h3>
        </div>
        <div class="panel-body" style="padding: 0;">
            <div id='search-result' class="list-group" style="margin-bottom:0; display: none;">
            </div>
            <p id='search-init' class="text-center" style="margin-top: 20px; margin-bottom: 20px; font-size: 12px;">Press the search button</p>
            <p id='search-failed' class="text-center" style="margin-top: 20px; margin-bottom: 20px; font-size: 12px; display: none">Not found the search result</p>
        </div>
    </div>
</div>