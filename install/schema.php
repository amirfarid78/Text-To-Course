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
            // Use local schema file instead of downloading from remote server
            $schemaFile = "schema.sql";
            $backupSchemaFile = "install/schema.sql";
            
            if (file_exists($schemaFile) || file_exists($backupSchemaFile)) {
                // Use the local schema file
                $queries = file_exists($schemaFile) ? 
                          file_get_contents($schemaFile) : 
                          file_get_contents($backupSchemaFile);
                
                // Execute the schema to create all tables first
                if ($sql->multi_query($queries)) {
                    // Process all results to ensure all queries are executed
                    do {
                        if ($result = $sql->store_result()) {
                            $result->free();
                        }
                    } while ($sql->more_results() && $sql->next_result());
                    
                    // Now that all tables are created, update the configuration
                    // Check if config table exists before trying to update it
                    $tableCheck = $sql->query("SHOW TABLES LIKE 'config'");
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                        $sql->query('UPDATE config SET client = "' . mysqli_real_escape_string($sql, $_POST['license']) . '"');
                    }
                    
                    // Check if settings table exists
                    $tableCheck = $sql->query("SHOW TABLES LIKE 'settings'");
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                        $sql->query('UPDATE settings SET setting_val = "' . mysqli_real_escape_string($sql, $_POST['client']) . '" WHERE setting = "client"');
                        $sql->query('UPDATE settings SET setting_val = "' . mysqli_real_escape_string($sql, $_POST['license']) . '" WHERE setting = "license"');
                        $sql->query('UPDATE settings SET setting_val = "' . mysqli_real_escape_string($sql, $_POST['fakeUsers']) . '" WHERE setting = "fakeUserLimit"');
                        $sql->query('UPDATE settings SET setting_val = "0" WHERE setting = "fakeUserUsage"');
                        $sql->query('UPDATE settings SET setting_val = "' . mysqli_real_escape_string($sql, $_POST['domainsUsage']) . '" WHERE setting = "domainsUsage"');
                        $sql->query('UPDATE settings SET setting_val = "' . mysqli_real_escape_string($sql, $_POST['domainsLimit']) . '" WHERE setting = "domainsLimit"');
                        $sql->query('UPDATE settings SET setting_val = "1" WHERE setting = "premium"');
                    }
                    
                    // Check if client table exists
                    $tableCheck = $sql->query("SHOW TABLES LIKE 'client'");
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                        $sql->query('INSERT INTO client (client) VALUES ("' . mysqli_real_escape_string($sql, $_POST['fullData']) . '")');
                    }

                    $check_bar = substr($url, -1);
                    if ($check_bar != '/') {
                        $url = $url . '/';
                    }

                    $mobile_site = $url . "mobile";
                    
                    // Update config table if it exists
                    $tableCheck = $sql->query("SHOW TABLES LIKE 'config'");
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                        $sql->query('UPDATE config SET mobile_site = "' . mysqli_real_escape_string($sql, $mobile_site) . '"');
                    }
                    
                    // Update settings table if it exists
                    $tableCheck = $sql->query("SHOW TABLES LIKE 'settings'");
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                        $sql->query('UPDATE settings SET setting_val = "' . mysqli_real_escape_string($sql, $mobile_site) . '" WHERE setting = "mobile_site"');
                    }

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
                        $arr['reason'] = 'Database Installed Successfully';
                    }
                } else {
                    $arr['reason'] = "Error executing database schema: " . $sql->error;
                }
            } else {
                $arr['reason'] = "Missing database schema file. Please ensure schema.sql exists in the installation directory.";
            }
        }
    }
}

echo json_encode($arr);