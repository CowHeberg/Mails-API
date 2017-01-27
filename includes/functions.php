<?php
/**
 * Created by PhpStorm.
 * User: rochb
 * Date: 27/01/2017
 * Time: 21:42
 */

function writeLog($app, $text){
    if($app['logs'] == true) {
        $log = '[' . date("h:i d/m/y") . '] ' . $text . ' ' . PHP_EOL;
        error_log($log, 3, __DIR__ . '/../logs/log_' . date("d_m_y") . '.log');
    }
}