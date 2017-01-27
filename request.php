<?php
/**
 * Created by PhpStorm.
 * User: rochb
 * Date: 27/01/2017
 * Time: 22:05
 */

foreach($_GET as $p => $val){
    $url = 'http://mail.local/index.php';
    $fields = array(
        $p => $val
    );

    $fields_string = '';
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    rtrim($fields_string, '&');

    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

    echo curl_exec($ch);

    curl_close($ch);
}