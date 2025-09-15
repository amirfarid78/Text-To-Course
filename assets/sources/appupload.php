<?php
header("Access-Control-Allow-Origin: *");
require_once ("../includes/core.php");
require_once ("../includes/custom/app_core.php");

//AWS
require 'aws/aws-autoloader.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

if (aws('enabled') == 'Yes') {
  // AWS Info
  $bucketName = aws('bucket');
  $IAM_KEY = aws('s3');
  $IAM_SECRET = aws('secret');
  // Connect to AWS
  try {
    $s3 = S3Client::factory(
      array(
        'credentials' => array(
          'key' => $IAM_KEY,
          'secret' => $IAM_SECRET
        ),
        'version' => 'latest',
        'region' => aws('region')
      )
    );
  } catch (Exception $e) {
    die("Error: " . $e->getMessage());
  }
}

function aws($val)
{
  global $mysqli;
  $config = $mysqli->query("SELECT setting_val FROM plugins_settings where plugin = 'amazon' and setting = '" . $val . "'");
  $result = $config->fetch_object();
  return $result->setting_val;
}
function watermark($val)
{
  global $mysqli;
  $config = $mysqli->query("SELECT setting_val FROM plugins_settings where plugin = 'watermark' and setting = '" . $val . "'");
  $result = $config->fetch_object();
  return $result->setting_val;
}

function getPhotoType($data)
{
  if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
    $data = substr($data, strpos($data, ',') + 1);
    $type = strtolower($type[1]); // jpg, png, gif

    if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'wav', 'mpeg', 'mp4', '3gp', 'm4a', 'aac', 'flac', 'mp3', 'mkv', 'ogg', 'webm', 'bmp', 'webp', 'heic', 'heif', 'avif'])) {
      throw new \Exception('invalid image type');
    }

    $data = base64_decode($data);

    if ($data === false) {
      throw new \Exception('base64_decode failed');
    } else {
      return $type;
    }
  } else {
    return false;
    // throw new \Exception('did not match data URI with image data');
    // return false;
  }
}

function regImage($base64img, $uid)
{
  global $sm;
  $file = $base64img;
  $time = time();
  $arr = array();
  // Check for upload errors
  if ($file['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($file['name']);
    $destinationPath = 'uploads/' . $uid . $time . '.' . $fileName;
    $thumbpath = 'uploads/thumb_' . $uid . $time . '.' . $fileName;

    $filepath = strtolower($destinationPath);
    if (strpos($filepath, '.php') !== false || strpos($filepath, '.py') !== false || strpos($filepath, '.htaccess') !== false || strpos($filepath, '.rb') !== false) {
      exit;
    }

    // Basic security checks (you should implement more robust validation)
    $allowedMimeTypes = [
      // Images
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/bmp',
      'image/webp',
      'image/svg+xml',
      'image/tiff',
      'image/jpg',
      'jpeg',
      'jpg',
      'png',
      'gif',
      'bmp',
      'webp',
      'heic',
      'heif',
      'avif',
      'svg',
      'tiff',
      'application',


      // Videos
      'video/mp4',
      'video/mpeg',
      'video/webm',
      'video/quicktime', // .mov
      'video/x-msvideo', // .avi
      'video/3gpp',
      'video/3gpp2',
      'mp4',
      'mpeg',
      'webm',
      'mov',
      'avi',
      '3gp',
      '3gpp',
      '3gpp2',
      'quicktime',
      'x-msvideo',
      'flv',
      'f4v',
      'f4p',
      'f4a',
      'f4b',
      'webm',
      'ogg',
      'ogv',
      'oga',
      'ogx',

    ];
    // if (in_array($file['type'], $allowedMimeTypes)) {
    if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
      // File uploaded successfully
      $purl = $sm['config']['site_url'] . 'assets/sources/' . $filepath;
      $thumburl = "";
      if (strpos($filepath, 'jpg') !== false || strpos($filepath, 'jpeg') !== false || strpos($filepath, 'png') !== false || strpos($filepath, 'JPG') !== false || strpos($filepath, 'JPEG') !== false || strpos($filepath, 'PNG') !== false) {
        make_thumb($filepath, $thumbpath, 200);
        $thumburl = $sm['config']['site_url'] . 'assets/sources/' . $thumbpath;
      }
      $arr['photo'] = $purl;
      $arr['thumb'] = $thumburl;
      http_response_code(200);
    } else {
      // Failed to move uploaded file
      $arr = ['status' => 'error', 'message' => 'Failed to move uploaded file'];
      http_response_code(500);
    }
    // } else {
    //   // Invalid file type
    //   $arr = ['status' => 'error', 'message' => 'Invalid file type'];
    //   http_response_code(400);
    // }
  } else {
    // Handle upload errors
    $arr = ['status' => 'error', 'message' => 'File upload error: ' . $file['error']];
    http_response_code(400);
  }

  header('Content-Type: application/json');
  echo json_encode($arr);
}

function upload($base64img, $uid)
{
  global $sm;
  $file = $base64img;
  $time = time();
  $arr = array();
  // Check for upload errors
  if ($file['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($file['name']);
    $destinationPath = 'uploads/' . $uid . $time . '.' . $fileName;
    $thumbpath = 'uploads/thumb_' . $uid . $time . '.' . $fileName;

    $filepath = strtolower($destinationPath);
    if (strpos($filepath, '.php') !== false || strpos($filepath, '.py') !== false || strpos($filepath, '.htaccess') !== false || strpos($filepath, '.rb') !== false) {
      exit;
    }

    // Basic security checks (you should implement more robust validation)
    $allowedMimeTypes = [
      // Images
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/bmp',
      'image/webp',
      'image/svg+xml',
      'image/tiff',
      'image/jpg',
      'jpeg',
      'jpg',
      'png',
      'gif',
      'bmp',
      'webp',
      'heic',
      'heif',
      'avif',
      'svg',
      'tiff',
      'application',


      // Videos
      'video/mp4',
      'video/mpeg',
      'video/webm',
      'video/quicktime', // .mov
      'video/x-msvideo', // .avi
      'video/3gpp',
      'video/3gpp2',
      'mp4',
      'mpeg',
      'webm',
      'mov',
      'avi',
      '3gp',
      '3gpp',
      '3gpp2',
      'quicktime',
      'x-msvideo',
      'flv',
      'f4v',
      'f4p',
      'f4a',
      'f4b',
      'webm',
      'ogg',
      'ogv',
      'oga',
      'ogx',

    ];
    // if (in_array($file['type'], $allowedMimeTypes)) {
    if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
      // File uploaded successfully
      $purl = $sm['config']['site_url'] . 'assets/sources/' . $filepath;
      $thumburl = "";
      if (strpos($filepath, 'jpg') !== false || strpos($filepath, 'jpeg') !== false || strpos($filepath, 'png') !== false || strpos($filepath, 'JPG') !== false || strpos($filepath, 'JPEG') !== false || strpos($filepath, 'PNG') !== false) {
        make_thumb($filepath, $thumbpath, 200);
        $thumburl = $sm['config']['site_url'] . 'assets/sources/' . $thumbpath;
      }
      $arr['photo'] = $purl;
      $arr['thumb'] = $thumburl;
      http_response_code(200);
    } else {
      // Failed to move uploaded file
      $arr = ['status' => 'error', 'message' => 'Failed to move uploaded file'];
      http_response_code(500);
    }
    // } else {
    //   // Invalid file type
    //   $arr = ['status' => 'error', 'message' => 'Invalid file type'];
    //   http_response_code(400);
    // }
  } else {
    // Handle upload errors
    $arr = ['status' => 'error', 'message' => 'File upload error: ' . $file['error']];
    http_response_code(400);
  }

  header('Content-Type: application/json');
  echo json_encode($arr);
}

function uploadVideo($base64img, $uid)
{
  global $sm;
  $file = $base64img;
  $time = time();
  $arr = array();
  // Check for upload errors
  if ($file['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($file['name']);
    $destinationPath = 'uploads/' . $uid . $time . '.' . $fileName;
    $thumbpath = 'uploads/thumb_' . $uid . $time . '.' . $fileName;

    $filepath = strtolower($destinationPath);
    if (strpos($filepath, '.php') !== false || strpos($filepath, '.py') !== false || strpos($filepath, '.htaccess') !== false || strpos($filepath, '.rb') !== false) {
      exit;
    }

    // Basic security checks (you should implement more robust validation)
    $allowedMimeTypes = [
      // Images
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/bmp',
      'image/webp',
      'image/svg+xml',
      'image/tiff',
      'image/jpg',
      'jpeg',
      'jpg',
      'png',
      'gif',
      'bmp',
      'webp',
      'heic',
      'heif',
      'avif',
      'svg',
      'tiff',
      'application',


      // Videos
      'video/mp4',
      'video/mpeg',
      'video/webm',
      'video/quicktime', // .mov
      'video/x-msvideo', // .avi
      'video/3gpp',
      'video/3gpp2',
      'mp4',
      'mpeg',
      'webm',
      'mov',
      'avi',
      '3gp',
      '3gpp',
      '3gpp2',
      'quicktime',
      'x-msvideo',
      'flv',
      'f4v',
      'f4p',
      'f4a',
      'f4b',
      'webm',
      'ogg',
      'ogv',
      'oga',
      'ogx',

    ];
    // if (in_array($file['type'], $allowedMimeTypes)) {
    if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
      // File uploaded successfully
      $purl = $sm['config']['site_url'] . 'assets/sources/' . $filepath;
      $thumburl = "";
      if (strpos($filepath, 'jpg') !== false || strpos($filepath, 'jpeg') !== false || strpos($filepath, 'png') !== false || strpos($filepath, 'JPG') !== false || strpos($filepath, 'JPEG') !== false || strpos($filepath, 'PNG') !== false) {
        make_thumb($filepath, $thumbpath, 200);
        $thumburl = $sm['config']['site_url'] . 'assets/sources/' . $thumbpath;
      }
      $arr['photo'] = $purl;
      $arr['thumb'] = $thumburl;
      http_response_code(200);
    } else {
      // Failed to move uploaded file
      $arr = ['status' => 'error', 'message' => 'Failed to move uploaded file'];
      http_response_code(500);
    }
    // } else {
    //   // Invalid file type
    //   $arr = ['status' => 'error', 'message' => 'Invalid file type'];
    //   http_response_code(400);
    // }
  } else {
    // Handle upload errors
    $arr = ['status' => 'error', 'message' => 'File upload error: ' . $file['error']];
    http_response_code(400);
  }

  header('Content-Type: application/json');
  echo json_encode($arr);
}

function uploadUserImage($base64img, $uid, $profile = "No")
{
  global $mysqli, $sm;
  $file = $base64img;
  $time = time();
  $arr = array();
  // Check for upload errors
  if ($file['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($file['name']);
    $destinationPath = 'uploads/' . $uid . $time . '.' . $fileName;
    $thumbpath = 'uploads/thumb_' . $uid . $time . '.' . $fileName;

    $filepath = strtolower($destinationPath);
    if (strpos($filepath, '.php') !== false || strpos($filepath, '.py') !== false || strpos($filepath, '.htaccess') !== false || strpos($filepath, '.rb') !== false) {
      exit;
    }

    // Basic security checks (you should implement more robust validation)
    $allowedMimeTypes = [
      // Images
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/bmp',
      'image/webp',
      'image/svg+xml',
      'image/tiff',
      'image/jpg',
      'jpeg',
      'jpg',
      'png',
      'gif',
      'bmp',
      'webp',
      'heic',
      'heif',
      'avif',
      'svg',
      'tiff',
      'application',


      // Videos
      'video/mp4',
      'video/mpeg',
      'video/webm',
      'video/quicktime', // .mov
      'video/x-msvideo', // .avi
      'video/3gpp',
      'video/3gpp2',
      'mp4',
      'mpeg',
      'webm',
      'mov',
      'avi',
      '3gp',
      '3gpp',
      '3gpp2',
      'quicktime',
      'x-msvideo',
      'flv',
      'f4v',
      'f4p',
      'f4a',
      'f4b',
      'webm',
      'ogg',
      'ogv',
      'oga',
      'ogx',

    ];
    // if (in_array($file['type'], $allowedMimeTypes)) {
    if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
      // File uploaded successfully
      $purl = $sm['config']['site_url'] . 'assets/sources/' . $filepath;
      $thumburl = "";
      if (strpos($filepath, 'jpg') !== false || strpos($filepath, 'jpeg') !== false || strpos($filepath, 'png') !== false || strpos($filepath, 'JPG') !== false || strpos($filepath, 'JPEG') !== false || strpos($filepath, 'PNG') !== false) {
        make_thumb($filepath, $thumbpath, 200);
        $thumburl = $sm['config']['site_url'] . 'assets/sources/' . $thumbpath;
      }
      $photoReview = 1;
      if ($sm['plugins']['settings']['photoReview'] == 'Yes' && !isset($_POST['adminPanel'])) {
        $photoReview = 0;
      }
      if ($profile == "Yes") {
        $photoReview = 0;
        $profil = 1;
        $checkProfilePicExist = $mysqli->query("SELECT * FROM users_photos WHERE u_id = '$uid' AND profile = 1");
        if ($checkProfilePicExist->num_rows > 0) {
          $mysqli->query("UPDATE users_photos SET photo = '$purl', thumb = '$thumburl', approved = '$photoReview', profile = 1 WHERE u_id = '$uid' AND profile = 1");
        } else {
          $mysqli->query("INSERT INTO users_photos(u_id,photo,thumb,approved,profile)
    VALUES ('$uid','$purl', '$thumburl','" . $photoReview . "',1)");
        }
      } else {
        $mysqli->query("INSERT INTO users_photos(u_id,photo,thumb,approved)
    VALUES ('$uid','$purl', '$thumburl','" . $photoReview . "')");
      }
      $arr['user']['photos'] = userAppPhotos($uid);
      $arr['user']['photo'] = $purl;
      $arr['user']['thumb'] = $thumburl;
      $arr['photo'] = $purl;
      $arr['thumb'] = $thumburl;
      http_response_code(200);
    } else {
      // Failed to move uploaded file
      $arr = ['status' => 'error', 'message' => 'Failed to move uploaded file'];
      http_response_code(500);
    }
    // } else {
    //   // Invalid file type
    //   $arr = ['status' => 'error', 'message' => 'Invalid file type'];
    //   http_response_code(400);
    // }
  } else {
    // Handle upload errors
    $arr = ['status' => 'error', 'message' => 'File upload error: ' . $file['error']];
    http_response_code(400);
  }

  header('Content-Type: application/json');
  echo json_encode($arr);
}

function uploadFeedImage($base64img, $uid, $feedid)
{
  global $mysqli, $sm;
  if (!isset($sm['user'])) {
    getUserInfo($uid);
  }
  $file = $base64img;
  $time = time();
  $arr = array();
  // Check for upload errors
  if ($file['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($file['name']);
    $destinationPath = 'uploads/' . $uid . $time . '.' . $fileName;
    $thumbpath = 'uploads/thumb_' . $uid . $time . '.' . $fileName;

    $filepath = strtolower($destinationPath);
    if (strpos($filepath, '.php') !== false || strpos($filepath, '.py') !== false || strpos($filepath, '.htaccess') !== false || strpos($filepath, '.rb') !== false) {
      exit;
    }

    // Basic security checks (you should implement more robust validation)
    $allowedMimeTypes = [
      // Images
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/bmp',
      'image/webp',
      'image/svg+xml',
      'image/tiff',
      'image/jpg',
      'jpeg',
      'jpg',
      'png',
      'gif',
      'bmp',
      'webp',
      'heic',
      'heif',
      'avif',
      'svg',
      'tiff',
      'application',


      // Videos
      'video/mp4',
      'video/mpeg',
      'video/webm',
      'video/quicktime', // .mov
      'video/x-msvideo', // .avi
      'video/3gpp',
      'video/3gpp2',
      'mp4',
      'mpeg',
      'webm',
      'mov',
      'avi',
      '3gp',
      '3gpp',
      '3gpp2',
      'quicktime',
      'x-msvideo',
      'flv',
      'f4v',
      'f4p',
      'f4a',
      'f4b',
      'webm',
      'ogg',
      'ogv',
      'oga',
      'ogx',

    ];
    if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
      $purl = $sm['config']['site_url'] . 'assets/sources/' . $filepath;
      $thumburl = "";
      if (strpos($filepath, 'jpg') !== false || strpos($filepath, 'jpeg') !== false || strpos($filepath, 'png') !== false || strpos($filepath, 'JPG') !== false || strpos($filepath, 'JPEG') !== false || strpos($filepath, 'PNG') !== false) {
        make_thumb($filepath, $thumbpath, 200);
        $thumburl = $sm['config']['site_url'] . 'assets/sources/' . $thumbpath;
      }
      $photoReview = 1;
      $arr['photo'] = $purl;
      $arr['thumb'] = $thumburl;
      $mysqli->query("INSERT INTO feed_photos(feed_id,u_id,photo,thumb,approved) VALUES ('$feedid','$uid','$purl', '$thumburl','" . $photoReview . "')");
      $arr['feedPhoto'] = array('photo' => $purl, 'thumb' => $thumburl);
      http_response_code(200);
    } else {
      // Failed to move uploaded file
      $arr = ['status' => 'error', 'message' => 'Failed to move uploaded file'];
      http_response_code(500);
    }
  } else {
    // Handle upload errors
    $arr = ['status' => 'error', 'message' => 'File upload error: ' . $file['error']];
    http_response_code(400);
  }

  header('Content-Type: application/json');
  echo json_encode($arr);
}

switch ($_POST['action']) {
  case 'register':
    regImage(secureEncode($_POST['file']), secureEncode($_POST['uid']));
    break;
  case 'videoRecord':
    $arr = array();
    $data = base64_decode(preg_replace('#^data:video/\w+;base64,#i', '', secureEncode($_POST['base64'])));
    $time = time();
    $file = 'uploads/' . secureEncode($_POST['uid']) . $time . '.webm';
    $video = $sm['config']['site_url'] . 'assets/sources/' . $file;
    file_put_contents($file, $data);

    $mysqli->query("UPDATE videocall set r_id_video = '" . $video . "' where call_id = '" . secureEncode($_POST['callId']) . "' and r_id = '" . secureEncode($_POST['uid']) . "'");
    $mysqli->query("UPDATE videocall set c_id_video = '" . $video . "' where call_id = '" . secureEncode($_POST['callId']) . "' and c_id = '" . secureEncode($_POST['uid']) . "'");

    $arr['videoRecord'] = $video;
    $arr['called'] = secureEncode($_POST['called']);
    $arr['uid'] = secureEncode($_POST['uid']);
    echo json_encode($arr);
    break;
  case 'upload':
    upload($_FILES['file'], secureEncode($_POST['uid']));
    break;

  case 'uploadUserImage':
    uploadUserImage($_FILES['file'], secureEncode($_POST['uid']), secureEncode($_POST['is_profile_pic']));
    break;

  case 'uploadVideo':
    upload($_FILES['file'], secureEncode($_POST['uid']));
    break;
  case 'uploadFeed':
    uploadFeedImage($_FILES['file'], secureEncode($_POST['uid']), secureEncode($_POST['feedid']));
    break;
  case 'sendChat':
    $uid = secureEncode($_POST['uid']);
    $rid = secureEncode($_POST['rid']);
    $base64img = str_replace('data:image/jpeg;base64,', '', $_POST['base64']);
    $base64img = str_replace('data:image/png;base64,', '', $_POST['base64']);
    $data = base64_decode($base64img);
    $time = time();
    $file = 'uploads/' . $uid . $time . '.jpg';
    $photo = $sm['config']['site_url'] . '/assets/sources/' . $file;
    file_put_contents($file, $data);
    $mysqli->query("INSERT INTO chat (s_id,r_id,time,message,photo) VALUES ('" . $uid . "','" . $rid . "','" . $time . "','" . $photo . "' , 1)");
    $event = 'chat' . $rid . $uid;
    $arr['type'] = 'image';
    $arr['message'] = $photo;
    $arr['id'] = $uid;
    $arr['chatHeaderRight'] = '<div class="js-message-block" id="you">
                <div class="message">
                    <div class="brick brick--xsm brick--hover">
                        <div class="brick-img profile-photo" data-src="' . $photo . '"></div>
                    </div>
                    <div class="message__txt">
                        <span class="lgrey message__time" style="margin-right: 15px;">' . date("H:i", $time) . '</span>
                        <div class="message__name lgrey"></div>
                        <a href="#img' . $time . '">
                            <p class="montserrat chat-text">
                                <div class="message__pic_ js-wrap" style="cursor:pointer;">
                                    <img  src="' . $photo . '" />
                                </div>
                            </p>
                        </a>
                    </div>
                </div>
            </div>  
        ';
    if (is_numeric($sm['plugins']['pusher']['id'])) {
      $sm['push']->trigger($sm['plugins']['pusher']['key'], $event, $arr);
    }
    break;
}


function make_thumb($src, $dest, $desired_width)
{
  $imgType = get_image_type($src);
  if (strpos($imgType, 'png') !== false) {
    $source_image = imagecreatefrompng($src);
  } else {
    $source_image = imagecreatefromjpeg($src);
  }
  $width = imagesx($source_image);
  $height = imagesy($source_image);
  $desired_height = floor($height * ($desired_width / $width));
  $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
  imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
  imagejpeg($virtual_image, $dest);
}

function get_image_type($filename)
{
  $img = getimagesize($filename);
  if (!empty($img[2]))
    return image_type_to_mime_type($img[2]);
  return false;
}

function awsThumb($url, $filename, $width = 200, $height = true)
{

  $image = ImageCreateFromString(file_get_contents($url));
  $height = $height === true ? (ImageSY($image) * $width / ImageSX($image)) : $height;
  $output = ImageCreateTrueColor($width, $height);
  ImageCopyResampled($output, $image, 0, 0, 0, 0, $width, $height, ImageSX($image), ImageSY($image));
  ImageJPEG($output, $filename, 95);
  return $filename;
}