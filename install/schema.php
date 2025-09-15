<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "";
$pass = "";
$port = "3306";
$url = "";
$database = "";
$sql = "";

function secureEncode($string)
{
    global $sql;
    $string = trim($string);
    $string = mysqli_real_escape_string($sql, $string);
    $string = htmlspecialchars($string, ENT_QUOTES);
    $string = str_replace('\\r\\n', '<br>', $string);
    $string = str_replace('\\r', '<br>', $string);
    $string = str_replace('\\n\\n', '<br>', $string);
    $string = str_replace('\\n', '<br>', $string);
    $string = str_replace('\\n', '<br>', $string);
    $string = stripslashes($string);
    $string = str_replace('&amp;#', '&#', $string);
    return $string;
}

$arr = array();
$arr['error'] = 1;
$arr['reason'] = 'No post data';
if (isset($_POST['host'])) {
    $host = $_POST['host'];
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $port = $_POST['port'];
    $url = $_POST['url'];
    $database = $_POST['database'];
    $license = $_POST['license'];

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        $arr['reason'] = "Please write a valid URL (with http:// or https:// )";
        echo json_encode($arr);
        exit;
    }

    if ($user == "" || $database == "" || $license == "" || $url == '') {
        $arr['reason'] = "Please fill in all fields as required.";
    } else {
        $sql = new mysqli($host, $user, $pass, $database, $port);
        if (mysqli_connect_errno()) {
            $arr['reason'] = mysqli_connect_error();
        } else {
            $check1 = 0;
            $check2 = 0;
            if (file_exists("schema.sql")) {
                // Try to download schema.sql from remote server
                $license = $_POST['license'];
                $domain = $_SERVER['HTTP_HOST']; // Get the current domain
                $schemaUrl = "https://lamatt.serv00.net/license/schema.php?purchaseCode=" . urlencode($license) . "&domain=" . urlencode($domain);
                // Remove extra slash after .php
                $remoteSchema = @file_get_contents($schemaUrl);
                if ($remoteSchema !== false) {
                    // Verify we received SQL content, not HTML or error message
                    if (strpos($remoteSchema, 'CREATE TABLE') !== false || strpos($remoteSchema, 'INSERT INTO') !== false) {
                        file_put_contents('schema.sql', $remoteSchema);
                        $check1 = 1;
                    } else {
                        $check1 = 0;
                    }
                } else {
                    $check1 = 0;
                }
            }
            if (file_exists("install/schema.sql")) {
                // Try to download schema.sql from remote server
                $license = $_POST['license'];
                $domain = $_SERVER['HTTP_HOST']; // Get the current domain
                $schemaUrl = "https://lamatt.serv00.net/license/schema.php?purchaseCode=" . urlencode($license) . "&domain=" . urlencode($domain);
                // Remove extra slash after .php
                $remoteSchema = @file_get_contents($schemaUrl);
                if ($remoteSchema !== false) {
                    // Verify we received SQL content, not HTML or error message
                    if (strpos($remoteSchema, 'CREATE TABLE') !== false || strpos($remoteSchema, 'INSERT INTO') !== false) {
                        file_put_contents('install/schema.sql', $remoteSchema);
                        $check2 = 1;
                    } else {
                        $check2 = 0;
                    }
                } else {
                    $check2 = 0;
                }
            }
            if ($check1 == 0 && $check2 == 0) {
                $arr['reason'] = "Missing database file schema.sql";
            } else {
                $queries = file_get_contents("schema.sql");
                $sql->multi_query($queries);
                while ($sql->next_result()) {
                    if (!$sql->more_results()) {
                        break;
                    }
                }
                $sql->query('update config set client = "' . $_POST['license'] . '"');
                $sql->query('update settings set setting_val = "' . $_POST['client'] . '" where setting = "client"');
                $sql->query('update settings set setting_val = "' . $_POST['license'] . '" where setting = "license"');
                $sql->query('update settings set setting_val = "' . $_POST['fakeUsers'] . '" where setting = "fakeUserLimit"');
                $sql->query('update settings set setting_val = "0" where setting = "fakeUserUsage"');
                $sql->query('update settings set setting_val = "' . $_POST['domainsUsage'] . '" where setting = "domainsUsage"');
                $sql->query('update settings set setting_val = "' . $_POST['domainsLimit'] . '" where setting = "domainsLimit"');
                $sql->query('update settings set setting_val = "1" where setting = "premium"');
                $sql->query('INSERT INTO client (client) VALUES ("' . $_POST['fullData'] . '")');



                $check_bar = substr($url, -1);
                if ($check_bar != '/') {
                    $url = $url . '/';
                }

                $mobile_site = $url . "mobile";
                $sql->query('update config set mobile_site = "' . $mobile_site . '"');
                $sql->query('update settings set setting_val = "' . $mobile_site . '" where setting = "mobile_site"');

                $config = file_get_contents("config.tmp");
                $config = str_replace('%1', $host, $config);
                $config = str_replace('%2', $database, $config);
                $config = str_replace('%3', $user, $config);
                $config = str_replace('%4', $pass, $config);
                $config = str_replace('%5', $url, $config);
                $b = file_put_contents("../assets/includes/config.php", $config);
                if ($b === false) {
                    $arr['reason'] = "Failed to write to config.php file in parent directory.";
                } else {
                    $sql->close();
                    $arr['error'] = 0;
                    $arr['reason'] = 'Database Installed';
                }
            }
        }
    }
}

echo json_encode($arr);
