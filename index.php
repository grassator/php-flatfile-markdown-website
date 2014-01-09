<?php

require_once dirname(__FILE__).'/lib/PhpFlatFileMarkdownWebsite.php';

$engine = new PhpFlatFileMarkdownWebsite();

// Add your configurations below this line but before call to start()
$engine->setDefaultTitle('PHP Flat-file Markdown Website Demo');

$engine->start();
