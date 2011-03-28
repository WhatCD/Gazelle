<?
ob_start();

echo '0';
ob_flush();
sleep(20);

echo '20';
ob_flush();
sleep(20);

echo '40';
ob_flush();
sleep(20);

echo '60';
ob_end_flush();

