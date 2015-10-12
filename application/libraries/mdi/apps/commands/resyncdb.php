<?php


if ( !function_exists('resyncdb')) {
    function resyncdb($ci, $model=NULL) {
        //middle.fixme
        $ci->mdi->load('features/dbsyncher');

        if (isset($model) && $model) {
            $ci->mdi->dbsyncher->drop($model);
            $ci->mdi->dbsyncher->sync($model);
        } else {
            //middle.fixme
            return 'resyncdb params error';
        }

        return 'resyncdb completed';
    }
}
