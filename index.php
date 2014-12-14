<?php
include 'vendor/autoload.php';

$p3tfile = 'test.p3t';
$extract_location = 'extracted/';

$p3t = new P3TExtractor\Extractor($p3tfile, $extract_location, true);
$p3t->parse();
$p3t->dump_files();
