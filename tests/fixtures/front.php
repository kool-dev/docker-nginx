<?php
header('Content-Type: text/plain');
echo 'kool php fixture|' . $_GET['value'] . '|' . $_SERVER['SCRIPT_FILENAME'];
