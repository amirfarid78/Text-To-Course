<?php
function getMaximumFileUploadSize(){  
    return min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));  
}  
function convertPHPSizeToBytes($sSize){
    $sSuffix = strtoupper(substr($sSize, -1));
    if (!in_array($sSuffix,array('P','T','G','M','K'))){
        return (int)$sSize;  
    } 
    $iValue = substr($sSize, 0, -1);
    switch ($sSuffix) {
        case 'P':
            $iValue *= 1024;
        case 'T':
            $iValue *= 1024;
        case 'G':
            $iValue *= 1024;
        case 'M':
            $iValue *= 1024;
        case 'K':
            $iValue *= 1024;
            break;
    }
    return (int)$iValue;
}

$extensions = get_loaded_extensions();
$uploadSize = getMaximumFileUploadSize() / 1024 / 1024;


echo_title("Mandatory requirements");
check(version_compare(phpversion(), '7.0', '>='), sprintf('Checking that PHP version (%s installed)', phpversion()), 'Install PHP 7.x or newer (current version is '.phpversion(), true);
check(is_writable(__DIR__.'/..'), sprintf('Ensuring software directory is writable'), 'Change the permissions of the ' . __DIR__.'/../ directory so that the web server can write in it, required for the updates', true);
check(function_exists('mysqli_connect'), sprintf('Checking that MySqli extension is installed'), 'Install Mysqli extension. Class Mysqli must be defined', true);
check(ini_get('allow_url_fopen'), 'Checking that allow_url_fopen is enabled', 'Set allow_url_fopen to on in php.ini', true);

check(extension_loaded("intl"), sprintf('Checking that intl support is available'), 'Install the pecl/intl library or compile PHP with intl support. intl must be loaded.', true);
check(extension_loaded("gd"), sprintf('Checking that gd support is available'), 'Install the gd library or compile php with gd support.', true);
check(extension_loaded("fileinfo"), sprintf('Checking that fileinfo support is available'), 'Install the pecl/fileinfo library or compile PHP with fileinfo support. fileinfo must be loaded for proper mime type recognition.', true);
check(extension_loaded("mbstring"), sprintf('Checking that multi-byte support is available'), 'Install the pecl/fileinfo library or compile PHP with multi-byte (mbstring) support.', true);
check(extension_loaded("curl"), sprintf('Checking that curl support is available'), 'Install the pecl/fileinfo library or compile PHP with curl support.', true);


echo_title("Optional checks");
check(true, 'Server MAX upload file size (upload_max_filesize,post_max_size) is '.$uploadSize.'MB we recommend to rise it up to 50MB so users can upload media without limitations', '', false);
check(function_exists('utf8_decode'), 'Checking that the utf8_decode() is available', 'Install and enable the XML extension', false);
check(class_exists('DomDocument'), 'Checking that the PHP-XML module is installed', 'Install and enable the php-xml module', false);
check(function_exists('token_get_all'), 'Checking that the token_get_all() function is available', 'Install and enable the Tokenizer extension (highly recommended)', false);
check(function_exists('mb_strlen'), 'Checking that the mb_strlen() function is available', 'Install and enable the mbstring extension', false);
check(function_exists('iconv'), 'Checking that the iconv() function is available', 'Install and enable the iconv extension', false);


check(!ini_get('magic_quotes_gpc'), 'Checking that php.ini has magic_quotes_gpc set to off', 'Set magic_quotes_gpc to off in php.ini', false);
check(!ini_get('register_globals'), 'Checking that php.ini has register_globals set to off', 'Set register_globals to off in php.ini', false);
check(!ini_get('session.auto_start'), 'Checking that php.ini has session.auto_start set to off', 'Set session.auto_start to off in php.ini', false);


// PHPExcel
echo_title("PHPExcel");
check(extension_loaded("mbstring"), sprintf('Checking that multi-byte support is available'), 'Install the pecl/fileinfo library or compile PHP with multi-byte (mbstring) support.', true);
check(0 == ini_get('mbstring.func_overload'), sprintf('Checking that multi-byte lib is tuned well'), 'Set param "mbstring.func_overload" to value "0" at your php.ini. Example mbstring.func_overload = 0', true);
check(extension_loaded("gd"), sprintf('Checking that gd support is available'), 'Install the gd library or compile php with gd support.', true);
check(class_exists('DomDocument'), 'Checking that the PHP-XML module is installed', 'Install and enable the php-xml module', true);
check(extension_loaded("zip"), sprintf('Checking that php_zip support is available'), 'Install the php_zip extension. http://pecl.php.net/package/zip', true);


function check($boolean, $message, $help = '', $fatal = false){
    if (is_cli()) {
        echo $boolean ? "  OK        " : sprintf("\n\n[[%s]] ", $fatal ? ' ERROR ' : 'WARNING');
        echo sprintf("$message%s\n", $boolean ? '' : ': FAILED');

        if (!$boolean) {
            echo "*** $help ***\n";
            if ($fatal) {
                global $server_error;
                $server_error = true;
                die("You must fix this problem before resuming the check.\n");
            }
        }
    } else {
        if ($boolean) {
            $color = '#fff';
            $image = '<svg style="margin-right:5px" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#00cac7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        } elseif ($fatal) {
            global $server_error;
            $server_error = true;
            $color = '#ff6768';
            $image = '<svg style="margin-right:5px" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12" y2="16"></line></svg>';
        } else {
            $color = '#fff';
            $image = '<svg style="margin-right:5px" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#7179ff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12" y2="16"></line></svg>';

        }

        echo sprintf('
<div style="background-color: %s; padding: 4px; margin: 3px; border: 1px #ddd solid; font-size: 14px">
    <div style="float: left">%s</div>
    <div style="float: left; margin-top: 7px; text-align: left;width:92%%">%s%s</div>
    <div style="clear: both"></div>
</div>', $color, $image, $message, !$boolean ? '<div style="background-color: #fff; padding:5px">What to do'.($fatal ? '' : ' (<em>optional</em>)').':<br>'.$help.'</div>' : '');
    }
}

function echo_title($title){
    if (is_cli()) {
        echo "\n** $title **\n\n";
    } else {
        echo "<h3>$title</h3>";
    }
}


function get_ini_path(){
    if ($path = get_cfg_var('cfg_file_path')) {
        return $path;
    }
    return 'WARNING: not using a php.ini file';
}

function is_cli(){
    return !isset($_SERVER['HTTP_HOST']);
}
