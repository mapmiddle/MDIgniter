<?php $this->mdi->statics_lazy('js', 'mdi/admin/admin-model-table.js'); ?>

<div class="model-table container">
    <div>
        <h1><?php echo $this->parse_model_table['_MODEL']; ?>
            <small>(<?php echo $this->parse_model_table['_LABEL']; ?>) (Table : <?php echo $this->parse_model_table['_TABLE']; ?>)</small>
        </h1>
    </div>
    <table class="table table-hover">
        <thead>
        <tr>
            <th>
                <input type="checkbox" class="row-checkbox-all" />
            </th>
            <?php
                foreach ($this->parse_model_table['_OBJECT']->default_fields as $field => $attribute){
                    if (!$this->parse_model_table['_OBJECT']->{$field.'_has_admin'}('hide')) {
                        ?><th><?php
                            echo $this->parse_model_table['_OBJECT']->{$field.'_label_get'}();
                        ?></th><?php
                    }
                }
            ?>
        </tr>
        </thead>
        <tbody>
            <?php
                foreach ($this->parse_model_table['_ROWS']->all as $row) {
                    ?><tr class='link' data-href="<?php echo current_url().'/'.$row->id; ?>">
                        <td>
                            <input type="checkbox" class="row-checkbox" data-id="<?php echo $row->id; ?>"/>
                        </td>
                    <?php
                        foreach ($row->default_fields as $field => $attribute){
                            if (!$row->{$field.'_has_admin'}('hide')) {
                                ?><td><?php
                                echo $row->{$field . '_display'}();
                                ?></td><?php
                            }
                        }
                    ?></tr><?php
                }
            ?>
        </tbody>
    </table>
    <div class="paginator">
        <?php
            foreach ($this->parse_model_table['_ROWS']->paged->paginator as $number => $attribute) {
                if (isset($attribute['begin'])) {
                    if (!isset($attribute['first'])) {
                        ?><span class="number"><a href="<?php echo current_url().'?page=1'; ?>">1</a></span><?php
                        ?><span class="number"><a href="<?php echo current_url().'?page='.(string)($number-1); ?>"><</a></span><?php
                    }
                }

                ?><span class="number
                    <?php
                        if (isset($attribute['current'])) {
                            echo 'select';
                        }
                    ?>"><a href="<?php echo current_url().'?page='.$number; ?>"><?php
                    echo $number
                ?></a></span><?php

                if (isset($attribute['end'])) {
                    if (!isset($attribute['last'])) {
                        ?><span class="number"><a href="<?php echo current_url().'?page='.(string)($number+1); ?>">></a></span><?php
                        ?><span class="number">
                            <a href="<?php echo current_url().'?page='.$this->parse_model_table['_ROWS']->paged->total_pages; ?>"
                                ><?php echo $this->parse_model_table['_ROWS']->paged->total_pages; ?></a>
                        </span><?php
                    }
                }
            }
        ?>
    </div>
</div>

<nav class="navbar navbar-inverse navbar-fixed-bottom">
    <div class="container">
        <div class="navbar-right">
            <div class="item">
                <a href="<?php echo current_url().'/new'; ?>">
                    <span class="btn btn-sm btn-primary">New</span>
                </a>
            </div>
            <div class="item">
                <a id="btn-delete" href="<?php echo current_url().'/delete'; ?>">
                    <span class="btn btn-sm btn-danger">Delete the selected items</span>
                </a>
            </div>
        </div>
    </div>
</nav>