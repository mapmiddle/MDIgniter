<?php


require_once(APPPATH.'libraries/mdi/libs/docblockreader/docblockreader.php');

class Annotation {
    public function parse($class, $method=NULL) {
        if ($method) {
            $reader = new DocBlockReader\Reader($class, $method);
        } else {
            $reader = new DocBlockReader\Reader($class);
        }

        return $reader->getParameters();
    }
}