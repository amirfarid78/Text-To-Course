<?php
ini_set('max_execution_time', 90);
require ('assets/includes/config.php');
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);
if (mysqli_connect_errno()) {
    exit(mysqli_connect_error());
}
$updated = false;
$aV = $_GET['version'];

$zipFilePath = 'updates/update-' . $aV . '.zip';

if (file_exists($zipFilePath)) {
    unlink($zipFilePath);
}

$newUpdate = file_get_contents('https://lamatt.serv00.net/updates/lamat/update-' . $aV . '.zip');
$dlHandler = fopen($zipFilePath, 'w');
if (!fwrite($dlHandler, $newUpdate)) {
    exit();
}
fclose($dlHandler);

$zip = new ZipArchive();
if ($zip->open($zipFilePath) === true) {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);
        $pathname = $zip->statIndex($i)['name'];
        $fileDir = dirname($pathname);

        // Continue if it's not a file (it's a directory)
        if (substr($pathname, -1, 1) === '/') {
            continue;
        }

        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }

        $contents = $zip->getFromIndex($i);
        $contents = str_replace("\r\n", "\n", $contents);

        if ($pathname === 'upgrade.php') {
            $upgradeExec = fopen('upgrade.php', 'w');
            fwrite($upgradeExec, $contents);
            fclose($upgradeExec);
            include ('upgrade.php');
            unlink('upgrade.php');
        } else if ($pathname === 'upgrade.sql') {
            global $mysqli;
            $sqlExec = fopen('upgrade.sql', 'w');
            fwrite($sqlExec, $contents);
            fclose($sqlExec);

            $queries = file_get_contents("upgrade.sql");
            $mysqli->multi_query($queries);
            if ($mysqli->more_results()) {
                while ($mysqli->next_result()) {
                    if (!$mysqli->more_results()) {
                        break;
                    }
                }
            }
            unlink('upgrade.sql');
        } else {
            $updateThis = fopen($pathname, 'w');
            fwrite($updateThis, $contents);
            fclose($updateThis);
            unset($contents);
        }
        $updated = true;
    }
    $zip->close();
} else {
    // Handle the case where the zip file couldn't be opened
    exit('Error opening the zip file.');
}

if ($updated == true) {
    $mysqli->query("UPDATE settings set setting_val = '$aV' where setting = 'currentVersion'");
    $mysqli->query("UPDATE settings SET setting_val = 'No' WHERE setting = 'updateAvailable'");
    $mysqli->query("UPDATE settings SET setting_val = '0' WHERE setting = 'checkUpdate'");

    header('Location: index.php?page=admin&p=main_dashboard&updated=' . $aV);
    exit;
}