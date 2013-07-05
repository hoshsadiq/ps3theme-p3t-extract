<?php

namespace P3TExtractor;

class Autoloader {
    public function __construct() {
        spl_autoload_register(__CLASS__.'::load');
    }

    private function load($class) {
        $class = explode('\\', $class);
        if(count($class == 2)) {
            $namespace = $class[0];
            $class = $class[1];
            if($namespace == __NAMESPACE__) {
                require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.$class.'.php');
            }
        }
    }
}

new Autoloader();