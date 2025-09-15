<?php
header('Content-Type: application/json');
require('../assets/includes/config.php');
$mysqli = new mysqli($db_host, $db_username, $db_password,$db_name);
$mysqli->set_charset('utf8mb4');
if (mysqli_connect_errno()) {
    exit(mysqli_connect_error());
}
$arr = array();
$arr['error'] = 1;
if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $password2 = trim($_POST['password2']);
    $salt = base64_encode($email.$password);
    $pswd = crypt($password,$salt);
    if ($password != $password2) {
        $arr['reason'] = "Passwords don't match";
    }
    else {
        if (strlen($password) < 4) {
            $arr['reason'] = "Please enter a password";
        }
        else {
            if ($email == "") {
                $arr['reason'] = "Enter an email";
            }
            else {
                $arr['error'] = 0;
                $mysqli->query('UPDATE users set email = "'.$email.'",pass = "'.$pswd.'" WHERE admin = 1');
                //header("Location: finish.php");
                //die;
            }
        }
    }
} else {
    $arr['reason'] = "Enter an email";
}

echo json_encode($arr);