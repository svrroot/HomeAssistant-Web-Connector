<?php
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
echo "<h1>UTF-8 Test</h1>";
echo "<p>Umlaute: äöüÄÖÜß</p>";
echo "<p>Sonderzeichen: °C € µ</p>";
echo "<p>PHP Charset: " . ini_get('default_charset') . "</p>";
echo "<p>Locale: " . setlocale(LC_ALL, 0) . "</p>";
echo "</body></html>";
?>