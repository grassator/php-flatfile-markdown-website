<?php

$start = microtime(true);

require_once dirname(__FILE__).'/lib/MarkEngine.php';

$engine = new MarkEngine();

// Add your configurations below this line but before call to start()
$engine->setMetaTitle('MarkEngine Demo');

$engine->start();

echo memory_get_usage() . '<br/>';
echo (microtime(true) - $start) * 1000 . 'ms';
