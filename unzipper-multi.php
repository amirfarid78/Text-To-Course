<?php

// Configuration
$splitFilesDir = './'; // Split ZIP files are in the same directory as this script (website root)
$extractedDir = './'; // Extract to the same directory (website root)
$combinedZipFile = 'combined.zip'; // The name of the temporary combined ZIP file
$output = ''; // Initialize output variable

// Ensure extraction directory exists and is writable (the website root should already exist)

function combineSplitZipFiles($splitFilesDir, $outputFilePath)
{
    $parts = glob($splitFilesDir . 'Archive.zip.*'); // Find files like Archive.zip.001, Archive.zip.002

    if (empty($parts)) {
        return 'Error: No split ZIP files found with the naming pattern "Archive.zip.*" in the website root.';
    }

    natsort($parts);

    $outputHandle = fopen($outputFilePath, 'wb');
    if (!$outputHandle) {
        return 'Error: Could not open the output file for writing: ' . $outputFilePath;
    }

    foreach ($parts as $partFile) {
        $inputHandle = fopen($partFile, 'rb');
        if ($inputHandle) {
            while (!feof($inputHandle)) {
                fwrite($outputHandle, fread($inputHandle, 8192));
            }
            fclose($inputHandle);
        } else {
            fclose($outputHandle);
            unlink($outputFilePath);
            return 'Error: Could not open part file: ' . $partFile;
        }
    }

    fclose($outputHandle);
    return 'Successfully combined the split ZIP files into: ' . basename($outputFilePath) . '<br>';
}

function unzipFile($zipFilePath, $destinationPath)
{
    $zip = new ZipArchive;
    if ($zip->open($zipFilePath) === TRUE) {
        $zip->extractTo($destinationPath);
        $zip->close();
        return 'Successfully extracted the archive to: ' . $destinationPath . '<br>';
    } else {
        return 'Error: Could not open or extract the combined archive.';
    }
}

// --- Main Script Execution ---

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_unzip']) && $_GET['start_unzip'] === 'true') {
    ob_start(); // Start output buffering

    $combinationResult = combineSplitZipFiles($splitFilesDir, $combinedZipFile);

    if (strpos($combinationResult, 'Successfully') !== false) {
        $unzipResult = unzipFile($combinedZipFile, $extractedDir);
        echo $unzipResult;

        if (strpos($unzipResult, 'Successfully') !== false) {
            if (unlink($combinedZipFile)) {
                echo 'Temporary combined ZIP file removed.<br>';
            } else {
                echo 'Warning: Could not remove the temporary combined ZIP file: ' . basename($combinedZipFile) . '<br>';
            }
        }
    } else {
        echo $combinationResult; // Output the error from combining
    }

    $output = ob_get_clean(); // Get the buffered output
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multipart Unzipper</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
        }

        .instructions {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .button-container {
            text-align: center;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        #status-message {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            background-color: #e6ffe6;
            display: none;
            /* Hidden by default */
            white-space: pre-wrap;
            /* Preserve formatting of PHP output */
        }

        #error-message {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #f00;
            background-color: #ffe6e6;
            display: none;
            /* Hidden by default */
            white-space: pre-wrap;
            /* Preserve formatting of PHP output */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Multipart Unzipper</h1>
        <div class="instructions">
            <p><strong>Instructions:</strong></p>
            <ol>
                <li>Ensure all parts of your split ZIP archive (e.g., <code>Archive.zip.001</code>,
                    <code>Archive.zip.002</code>, ...) are uploaded to the root directory of your website (the same
                    directory where this <code>unzipper.php</code> file will be).
                </li>
                <li>Click the button below to start the unzipping process.</li>
                <li>The status and any error messages will be displayed below the button.</li>
            </ol>
        </div>
        <div class="button-container">
            <button id="startUnzip">Start Unzip Process</button>
        </div>
        <div id="status-message"><?php if (isset($output))
            echo $output; ?></div>
        <div id="error-message"></div>
    </div>

    <script>
        document.getElementById('startUnzip').addEventListener('click', function () {
            document.getElementById('status-message').style.display = 'none';
            document.getElementById('error-message').style.display = 'none';

            fetch('unzipper.php?start_unzip=true') // Request the same PHP file
                .then(response => response.text())
                .then(data => {
                    if (data.includes('Successfully')) {
                        document.getElementById('status-message').textContent = data;
                        document.getElementById('status-message').style.display = 'block';
                        document.getElementById('error-message').style.display = 'none';
                    } else if (data.includes('Error')) {
                        document.getElementById('error-message').textContent = data;
                        document.getElementById('error-message').style.display = 'block';
                        document.getElementById('status-message').style.display = 'none';
                    } else {
                        document.getElementById('status-message').textContent = data;
                        document.getElementById('status-message').style.display = 'block';
                        document.getElementById('error-message').style.display = 'none';
                    }
                })
                .catch(error => {
                    document.getElementById('error-message').textContent = 'An error occurred while communicating with the server: ' + error;
                    document.getElementById('error-message').style.display = 'block';
                    document.getElementById('status-message').style.display = 'none';
                });
        });
    </script>
</body>

</html>