<?php

use M1\Env\Parser;

$realPath = realpath('.env');
if ($realPath) {
    $env = new Parser(file_get_contents('.env'), array('EXTERNAL' => 'external'));
    $arr = $env->getContent();
    foreach ($arr as $key => $val) {
        putenv($key . '=' . $val);
    }
}
