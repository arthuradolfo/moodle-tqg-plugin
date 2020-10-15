<?php

defined('MOODLE_INTERNAL') || die();

if ($CFG->version <= 2017051509.03) {
    function block_tqg_plugin_autoload($classname)
    {
        global $CFG;

        if (strpos($classname, 'block_tqg_plugin') === 0) {
            $classname = preg_replace('/^block_tqg_plugin\\\\/', '', $classname);

            $classdir = $CFG->dirroot . '/blocks/tqg_plugin/classes/';
            $path = $classdir . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';
            if (is_readable($path)) {
                require $path;
            }
        }
    }

    spl_autoload_register('block_tqg_plugin_autoload');
}