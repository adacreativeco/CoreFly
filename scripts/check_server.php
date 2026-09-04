<?php
$url = 'http://localhost:8000/health';
echo "Checking $url...\n";
$res = file_get_contents($url);
echo $res;
