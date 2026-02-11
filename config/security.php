<?php
require_once __DIR__ . '/../vendor/htmlpurifier/library/HTMLPurifier.auto.php';

function clean_html($dirty_html) {
    $config = HTMLPurifier_Config::createDefault();
    
    // Optional: Customize allowed tags
    $config->set('HTML.Allowed', 'p,b,i,u,a[href],ul,ol,li,br,strong,em');
    
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($dirty_html);
}