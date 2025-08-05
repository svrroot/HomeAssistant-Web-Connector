<?php
$host = 'db';
$user = 'root';
$pass = 'YOUR-ROOT-PASSWORD';
 
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    exit('Connection failed: '.mysqli_connect_error().PHP_EOL);
}
 
echo 'Successful database connection!'.PHP_EOL;
