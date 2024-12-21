<?php 

echo "<br>1<br>";
print_r($_SERVER['REMOTE_ADDR']);
echo "<br>2<br>";
print_r($_SERVER['HTTP_X_FORWARDED_FOR']);
