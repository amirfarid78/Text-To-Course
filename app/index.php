<?php
/* LAMAT By ABBBLE CO - abbbleco@gmail.com - https://lamat.infy.uk/ */

$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER[HTTP_HOST];
header('Location: ' . $actual_link);
return true;
