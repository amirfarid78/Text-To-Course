<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
  // header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
  header("Access-Control-Allow-Origin: *");
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
  exit(0);
}
header('Content-Type: application/json');
require_once ('../assets/includes/core.php');
if (isset($sm['user']['id'])) {
  $uid = $sm['user']['id'];
} else {
  $uid = 0;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  switch ($_GET['action']) {
    case 'golive':
      $uid = secureEncode($_GET['uid']);
      $channelId = secureEncode($_GET['channel_id']);
      $customTxt = secureEncode($_GET['message']);
      $streamName = secureEncode($_GET['stream_name']);
      $streamGoal = secureEncode($_GET['stream_goal']);
      $streamGoalType = secureEncode($_GET['stream_goal_type']);
      $goalDesc = secureEncode($_GET['goal_desc']);
      $thumbnail = secureEncode($_GET['thumbnail']);
      $isVerified = secureEncode($_GET['is_verified']);
      $followers = $mysqli->query("SELECT * FROM user_followers where u2 = '" . $uid . "'");
      $followers = $followers->num_rows;
      getUserInfo($uid, 0);
      $mysqli->query("INSERT INTO live (uid,id,viewers,start_time,custom_text,is_streaming,channel_id,stream_name,stream_goal,stream_goal_type,goal_desc,thumbnail,followers,is_verified,lat,lng,gender)
     VALUES('" . $uid . "', '" . $uid . "', 1, '" . time() . "', '" . $customTxt . "', 'Yes', '" . $channelId . "', '" . $streamName . "', '" . $streamGoal . "', '" . $streamGoalType . "', '" . $goalDesc . "', '" . $thumbnail . "', '" . $followers . "', '" . $isVerified . "', '" . $sm['user']['lat'] . "', '" . $sm['user']['lng'] . "','" . $sm['user']['gender'] . "') 
     ON DUPLICATE KEY UPDATE 
     viewers = 1, 
     start_time = '" . time() . "', 
     custom_text = '" . $customTxt . "', 
     is_streaming = 'Yes', 
     channel_id = '" . $channelId . "', 
     stream_name = '" . $streamName . "', 
     stream_goal = '" . $streamGoal . "', 
     stream_goal_type = '" . $streamGoalType . "', 
     goal_desc = '" . $goalDesc . "', 
     thumbnail = '" . $thumbnail . "', 
     followers = '" . $followers . "', 
     is_verified = '" . $isVerified . "', 
     lat = '" . $sm['user']['lat'] . "', 
     lng = '" . $sm['user']['lng'] . "', 
     gender = '" . $sm['user']['gender'] . "'");
      break;

    case 'getLiveStreams':
      $time = time();
      $arr = array();
      $filter = 'where is_streaming = "Yes"';
      $streams = getArray('live', $filter, 'id desc');
      $arr['result'] = 'empty';
      foreach ($streams as $s) {
        $streamTime2 = $s['start_time'] - time();
        $streamStart = $s['start_time'];
        $streamTime = gmdate("i:s", time() - $streamStart);
        $arr['streams'][] = array(
          "streamPhoto" => $s['thumbnail'],
          "streamCustomTxt" => $s['custom_text'],
          "streamName" => getData('users', 'name', 'WHERE id = ' . $s['uid']),
          "streamAge" => getData('users', 'age', 'WHERE id = ' . $s['uid']),
          "streamStart" => $s['start_time'],
          "streamTimeM" => $s['start_time'] * 1000,
          "streamTime" => $streamTime2,
          "streamTimeCounter" => $streamTime,
          "full" => $s
        );
        $arr['result'] = 'OK';
      }
      echo json_encode($arr);
      break;

    case 'addLiveComment':
      $stream_id = secureEncode($_GET['stream_id']);
      $userId = secureEncode($_GET['userId']);
      $comment = secureEncode($_GET['comment']);
      $commentType = isset($_GET['commentType']) ? secureEncode($_GET['commentType']) : 'text';

      // Get user information
      getUserInfo($userId, 1);
      $isVerify = $sm['profile']['verified'] ?? 0;
      $userImage = profilePhoto($userId);
      $userName = $sm['profile']['username'] ?? '';
      $fullName = $sm['profile']['name'] ?? '';

      // Insert comment into database
      $query = "INSERT INTO live_comments (stream_id, userId, comment, commentType, isVerify, userImage, userName, fullName) 
              VALUES ('$stream_id', '$userId', '$comment', '$commentType', '$isVerify', '$userImage', '$userName', '$fullName')";

      $result = $mysqli->query($query);

      $arr = array();
      if ($result) {
        $arr['result'] = 'OK';
        $arr['comment'] = array(
          'id' => $mysqli->insert_id,
          'stream_id' => $stream_id,
          'userId' => $userId,
          'comment' => $comment,
          'commentType' => $commentType,
          'isVerify' => $isVerify,
          'userImage' => $userImage,
          'userName' => $userName,
          'fullName' => $fullName
        );
      } else {
        $arr['result'] = 'error';
        $arr['message'] = 'Failed to add comment';
      }

      echo json_encode($arr);
      break;

    case 'getLiveStreamComments':
      header('Cache-Control: no-cache, no-store, must-revalidate');
      header('Pragma: no-cache');
      header('Expires: 0');

      $id = secureEncode($_GET['id']);
      $time = time();
      $arr = array();
      $filter = 'where stream_id = ' . $id;
      $comments = getArray('live_comments', $filter, 'id DESC', 'LIMIT 0,50');

      if (!empty($comments)) {
        $arr['comments'] = $comments;
        $arr['result'] = 'OK';
      } else {
        $arr['comments'] = [];
        $arr['result'] = 'empty';
      }

      echo json_encode($arr);
      break;

    case 'getLiveStreamUsers':
      $id = secureEncode($_GET['id']);
      $time = time();
      $arr = array();
      $filter = 'where live_stream_id = ' . $id;
      $users = getArray('live_viewers', $filter, 'id desc');
      if ($streams) {
        $arr['users'] = $users;
      }
      echo json_encode($arr);
      break;

    case 'addLiveStreamUser':
      $id = secureEncode($_GET['live_stream_id']);
      $uid = secureEncode($_GET['uid']);
      getUserInfo($uid, 1);
      $userPhoto = profilePhoto($uid);
      $userName = $sm['profile']['username'];
      $type = "viewer";
      $arr = array();
      $filter = 'where live_stream_id = ' . $id . ' and uid = ' . $uid;

      $cols = 'live_stream_id,uid,type,username,profile_pic';
      $vals = $id . ',' . $uid . ',' . $type;
      insertData('live_viewers', $cols, $vals);

      break;

    case 'removeLiveStreamUser':
      $liveStreamId = secureEncode($_GET['live_stream_id']);
      $uid = secureEncode($_GET['uid']);
      $filter = 'where live_stream_id = ' . $liveStreamId . ' and uid = ' . $uid;

      $mysqli->query("DELETE FROM live_viewers WHERE uid = " . $uid);

      break;
    case 'updateViewCount':
      $id = secureEncode($_GET['live_stream_id']);
      $arr = array();
      $filter = 'where id = ' . $id;
      $mysqli->query("UPDATE live SET viewers = viewers + 1 WHERE id = " . $uid);
      break;
    case 'minusViewCount':
      $id = secureEncode($_GET['live_stream_id']);
      $arr = array();
      $filter = 'where id = ' . $id;
      $mysqli->query("UPDATE live SET viewers = viewers - 1 WHERE id = " . $uid);
      break;
    case 'updateBattleUsers':
      $id = secureEncode($_GET['live_stream_id']);
      $join_users = secureEncode($_GET['battle_users']);
      $arr = array();
      $filter = 'where id = ' . $id;
      $mysqli->query("UPDATE live SET battle_users = '" . $join_users . "' WHERE id = " . $uid);
      break;

    case 'deleteLiveStream':
      $time = time();
      $uid = secureEncode($_GET['live_stream_id']);
      if (isset($_GET['sb'])) { //admin
        $uid = secureEncode($_GET['stream']);
        $sb = secureEncode($_GET['sb']);
        if ($sb == 1) {//ban
          $mysqli->query("INSERT INTO live_streamer_banned (uid) VALUES('" . $uid . "')");
        }
        if ($sb == 3) {//ban
          $mysqli->query("INSERT INTO live_streamer_banned (uid) VALUES('" . $uid . "')");
        }
        if ($sb == 2) {//unban
          $mysqli->query("DELETE FROM live_streamer_banned WHERE uid = '" . $uid . "'");
        }
        if ($sb == 0 || $sb == 3) {
          $mysqli->query("UPDATE live set end_time='" . $time . "' where uid = '" . $uid . "' order by id desc limit 1");
          $mysqli->query("UPDATE live set is_streaming='No' where uid = '" . $uid . "'");
          $notification = 'live' . $uid;
          $info['liveId'] = $uid;
          $info['type'] = 'end';

          if (is_numeric($sm['plugins']['pusher']['id'])) {
            $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
          }
        }
      } else {
        $mysqli->query("UPDATE live set end_time='" . $time . "' where uid = '" . $uid . "' order by id desc limit 1");
        $mysqli->query("UPDATE live set is_streaming='No' where uid = '" . $uid . "'");
      }
      break;
    case 'close':
      $live = secureEncode($_POST['id']);
      $mysqli->query("UPDATE live set viewers=viewers-1 where uid = '" . $live . "'");
      break;
    case 'endStream':
      $time = time();
      $uid = secureEncode($_GET['uid']);
      if (isset($_GET['sb'])) { //admin
        $uid = secureEncode($_GET['stream']);
        $sb = secureEncode($_GET['sb']);
        if ($sb == 1) {//ban
          $mysqli->query("INSERT INTO live_streamer_banned (uid) VALUES('" . $uid . "')");
        }
        if ($sb == 3) {//ban
          $mysqli->query("INSERT INTO live_streamer_banned (uid) VALUES('" . $uid . "')");
        }
        if ($sb == 2) {//unban
          $mysqli->query("DELETE FROM live_streamer_banned WHERE uid = '" . $uid . "'");
        }
        if ($sb == 0 || $sb == 3) {
          $mysqli->query("UPDATE live set end_time='" . $time . "' where uid = '" . $uid . "' order by id desc limit 1");
          $mysqli->query("UPDATE live set is_streaming='No' where uid = '" . $uid . "'");
          $notification = 'live' . $uid;
          $info['liveId'] = $uid;
          $info['type'] = 'end';

          if (is_numeric($sm['plugins']['pusher']['id'])) {
            $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
          }
        }
      } else {
        $mysqli->query("UPDATE live set end_time='" . $time . "' where uid = '" . $uid . "' order by id desc limit 1");
        $mysqli->query("UPDATE live set is_streaming='No' where uid = '" . $uid . "'");
      }
      break;

    case 'checkBanned':
      $viewerId = secureEncode($_GET['userId']);
      $streamId = secureEncode($_GET['streamId']);
      $check = getData('live_banned', 'streamer_id', 'WHERE streamer_id =' . $streamId . ' AND banned_id = ' . $viewerId);

      $arr = array();
      if ($check == 'noData') {
        $banned = 'No';
      } else {
        $banned = 'Yes';
      }
      echo $banned;
      break;

    case 'endStreamFromViewer':
      $time = time();
      $live = secureEncode($_GET['live']);
      $mysqli->query("UPDATE live set end_time='" . $time . "' where uid = '" . $live . "' order by id desc limit 1");
      $mysqli->query("UPDATE live set is_streaming='No' where uid = '" . $live . "'");
      break;

    case 'watching':
      $query = secureEncode($_GET['query']);
      $data = explode(',', $query);
      $liveId = $data[3];

      $notification = 'live' . $liveId;
      $info['liveId'] = $liveId;
      $info['type'] = 'watching';
      $info['name'] = secureEncode($data[1]);
      $info['photo'] = secureEncode($data[2]);
      $info['userId'] = secureEncode($data[0]);
      $info['time'] = date("H:i", time());

      if (is_numeric($sm['plugins']['pusher']['id'])) {
        $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
      }
      $mysqli->query("UPDATE live set viewers=viewers+1 where uid = '" . $liveId . "'");
      break;

    case 'status':
      $status = secureEncode($_GET['status']);
      $liveId = $sm['user']['id'];

      $notification = 'live' . $liveId;
      $info['liveId'] = $liveId;
      $info['type'] = 'status';
      $info['photo'] = profilePhoto($liveId);
      $info['price'] = secureEncode($_GET['price']);
      ;
      $info['status'] = $status;
      $info['time'] = date("H:i", time());

      if (is_numeric($sm['plugins']['pusher']['id'])) {
        $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
      }

      if ($status == 'private') {
        $mysqli->query("UPDATE live set in_private='Yes',private_price = '" . $info['price'] . "' where uid = '" . $liveId . "'");
      } else {
        $mysqli->query("UPDATE live set in_private='No',private_price = 0 where uid = '" . $liveId . "'");
      }

      break;

    case 'leave':
      $query = secureEncode($_GET['query']);
      $data = explode(',', $query);
      $liveId = $data[3];

      $notification = 'live' . $liveId;
      $info['liveId'] = $liveId;
      $info['type'] = 'leave';
      $info['name'] = secureEncode($data[1]);
      $info['photo'] = secureEncode($data[2]);
      $info['userId'] = secureEncode($data[0]);
      $info['time'] = date("H:i", time());

      if (is_numeric($sm['plugins']['pusher']['id'])) {
        $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
      }

      $mysqli->query("UPDATE live set viewers=viewers-1 where uid = '" . $liveId . "'");
      break;

    case 'sendLiveGift':
      $query = secureEncode($_GET['query']);
      $data = explode(',', $query);
      $liveId = $data[3];

      $notification = 'live' . $liveId;
      $info['liveId'] = $liveId;
      $info['type'] = 'gift';
      $info['name'] = secureEncode($data[1]);
      $info['photo'] = secureEncode($data[2]);
      $info['userId'] = secureEncode($data[0]);
      $info['gift'] = secureEncode($data[4]);
      $info['credits'] = secureEncode($data[5]);
      $info['time'] = date("H:i", time());

      if (is_numeric($sm['plugins']['pusher']['id'])) {
        $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
      }

      if ($sm['plugins']['live']['transferCredits'] == 'Yes') {
        $mysqli->query("UPDATE live set credits=credits+'" . $data[5] . "' where uid = '" . $liveId . "'");
      }

      break;

    case 'sendLiveMessage':
      $query = secureEncode($_GET['query']);
      $data = explode(';-B-;', $query);
      $liveId = $data[0];

      $notification = 'live' . $liveId;
      $info['liveId'] = $liveId;
      $info['type'] = 'message';
      $info['message'] = secureEncode($data[1]);
      $info['name'] = secureEncode($data[2]);
      $info['photo'] = secureEncode($data[3]);
      $info['userId'] = secureEncode($data[4]);
      $info['time'] = date("H:i", time());
      if (is_numeric($sm['plugins']['pusher']['id'])) {
        $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
      }
      break;

    case 'deleteAllStreamComments':
      $streamId = secureEncode($_GET['streamId']);
      $query = "DELETE FROM live_comments WHERE stream_id = '" . $streamId . "'";
      $result = $mysqli->query($query);

      if ($result) {
        echo json_encode(['success' => true, 'message' => 'Comments deleted successfully']);
      } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comments: ' . $mysqli->error]);
      }
      break;

    case 'blockUserLive':
      $bannedId = secureEncode($_GET['userId']);
      $streamId = $sm['user']['id'];
      $notification = 'live' . $streamId;

      $info['type'] = 'banned';
      $info['bannedId'] = $bannedId;
      $info['time'] = date("H:i", time());
      $info['name'] = getData('users', 'name', 'WHERE id =' . $bannedId);
      $info['photo'] = profilePhoto($bannedId);

      if (is_numeric($sm['plugins']['pusher']['id'])) {
        $sm['push']->trigger($sm['plugins']['pusher']['key'], $notification, $info);
      }
      $mysqli->query("INSERT INTO live_banned (streamer_id,banned_id) VALUES('" . $streamId . "','" . $bannedId . "')");
      break;

    case 'log':
      $min = secureEncode($_GET['min']);
      $sec = secureEncode($_GET['sec']);
      $totalSeconds = secureEncode($_GET['totalSeconds']);
      $callId = secureEncode($_GET['callId']);
      $time = $min . ":" . $sec;
      $date = time();
      $mysqli->query("UPDATE videocall set duration='" . $time . "',total_seconds='" . $totalSeconds . "' where call_id = '" . $callId . "'");
      break;

    case 'sendLiveInvite':
      $streamId = secureEncode($_GET['channelId']);
      $stream_userId = secureEncode($_GET['userId']);
      $stream_thumbnail = secureEncode($_GET['thumbnail']);
      $stream_name = secureEncode($_GET['name']);
      $full_name = secureEncode($_GET['fullName']);
      $invitee = secureEncode($_GET['invitee']);
      $mysql->query("INSERT INTO live_invite (stream_id,stream_title,stream_thumbnail,full_name,stream_user_id,invitee) VALUES('" . $streamId . "','" . $stream_name . "','" . $stream_thumbnail . "','" . $full_name . "','" . $stream_userId . "','" . $invitee . "')");
      break;
    case 'checkLiveInvite':
      $invitee = secureEncode($_GET['invitee']);
      $check = getData('live_invite', 'stream_id', 'WHERE invitee = ' . $invitee);
      if ($check == 'noData') {
        $arr = array();
        echo json_encode($arr);
      } else {
        $mysql->query("SELECT * FROM live_invite WHERE invitee = '" . $invitee . "'");
        $row = $mysql->fetch_object();
        $arr = array();
        $arr['streamId'] = $row->stream_id;
        $arr['streamTitle'] = $row->stream_title;
        $arr['streamThumbnail'] = $row->stream_thumbnail;
        $arr['fullName'] = $row->full_name;
        $arr['streamUserId'] = $row->stream_user_id;
        echo json_encode($arr);
      }
      break;
    case 'acceptLiveInvite':
      $invitee = secureEncode($_GET['invitee']);
      $streamId = secureEncode($_GET['streamId']);
      $mysql->query("DELETE FROM live_invite WHERE invitee = '" . $invitee . "' AND stream_id = '" . $streamId . "'");
      break;
    case 'declineLiveInvite':
      $invitee = secureEncode($_GET['invitee']);
      $mysql->query("DELETE FROM live_invite WHERE invitee = '" . $invitee . "'");
      break;
  }
}
$mysqli->close();