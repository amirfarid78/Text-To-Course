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
require_once('../assets/includes/core.php');
if (isset($sm['user']['id'])) {
    $uid = $sm['user']['id'];
} else {
    $uid = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($_GET['action']) {
        case 'loadDynamicReels':
            $arr = [];
            $order = 'time DESC';
            $uid = (int) secureEncode($_GET['uid']);
            $limit = (int) secureEncode($_GET['limit']);
            $customFilter = secureEncode($_GET['customFilter']);
            $trending = (int) secureEncode($_GET['trending']);
            $isDating = isset($_GET['isDating']) && (int) secureEncode($_GET['isDating']) == 1;

            // Get user info once
            getUserInfo($uid, 0);

            // Prepare base filter
            $filter = 'WHERE visible = 1';

            // Add dating filter if needed
            if ($isDating) {
                $looking = (int) getData('users', 's_gender', "WHERE id = {$uid}");
                $filter .= " AND gender = {$looking} AND uid <> {$sm['user']['id']}";
            }

            $reels = [];

            // Handle different filter types
            if (empty($customFilter) || $customFilter == 'all') {
                // Apply last viewed filter for non-trending content
                if ($trending === 0) {
                    $lastViewedReel = getData('users_reels_played', 'rid', "WHERE uid = {$sm['user']['id']}");
                    if ($lastViewedReel !== 'noData') {
                        $filter .= " AND id > {$lastViewedReel}";
                    }
                }

                // Set order for trending content
                if ($trending === 1) {
                    $order = 'likes DESC';
                }

                // Use string interpolation for LIMIT clause
                $reels = getArray('reels', $filter, $order, "LIMIT {$limit}");
            } else {
                // Handle custom filters
                switch ($customFilter) {
                    case 'liked':
                        $data = getArray('reels_likes', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'purchased':
                        $data = getArray('users_reels_purchases', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'me':
                        $filter = "WHERE visible = 1 AND uid = {$uid}";
                        $reels = getArray('reels', $filter, 'id DESC', '');
                        break;
                }
            }

            // Process results
            if (!empty($reels)) {
                $arr['reels'] = [];

                foreach ($reels as $reel) {
                    $reelId = (int) $reel['id'];
                    $reelUid = (int) $reel['uid'];

                    // Get user data efficiently
                    $userData = getDataArray('users', "id = {$reelUid}");
                    $userRank = getDataArray('users_ranking', "uid = {$reelUid}");
                    $isFollowing = selectC('user_followers', "WHERE u2 = {$reelUid} AND u1 = {$sm['user']['id']}");
                    $isSaved = selectC('reels_saves', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");

                    // Handle username fallback
                    $username = $userData['username'];
                    if (is_numeric($username)) {
                        $username = $userData['name'];
                    }

                    // Get profile photo
                    $profile_photo = profilePhoto($reelUid);

                    // Check if reel is liked
                    $checkLiked = getData('reels_likes', 'rid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                    $reelLiked = ($checkLiked !== 'noData') ? 1 : 0;

                    // Check if reel is purchased
                    $purchased = 'No';
                    if ($reel['reel_price'] > 0) {
                        $checkPurchased = getData('users_reels_purchases', 'uid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                        if ($checkPurchased !== 'noData' || $reelUid == $uid) {
                            $purchased = 'Yes';
                        }
                    } else if ($reelUid == $uid) {
                        $purchased = 'Yes';
                    }

                    // Check for stories
                    $storyFrom = $sm['plugins']['story']['days'];
                    $time = time();
                    $extra = 86400 * $storyFrom;
                    $storyFrom = $time - $extra;
                    $storiesFilter = "WHERE uid = {$reelUid} AND storyTime > {$storyFrom} AND deleted = 0";
                    $openStory = selectC('users_story', $storiesFilter);
                    $ranking = "newbie";
                    if ($userRank !== 'noData' && is_array($userRank) &&  isset($userRank['rank_type'])) {
                        $ranking = $userRank['rank_type'];
                    }

                    // Build reel data array
                    $arr['reels'][] = array(
                        "id" => $reelId,
                        "price" => (int) $reel['reel_price'],
                        "play" => $reel['reel_src'],
                        "cover" => $reel['reel_cover'],
                        "caption" => $reel['reel_meta'],
                        "photo" => $profile_photo,
                        "photo_big" => profilePhoto($reelUid, 1),
                        "purchased" => $purchased,
                        "user_id" => $reelUid,
                        "username" => $username,
                        "name" => $userData['name'],
                        "age" => (int) $userData['age'],
                        "city" => $userData['city'],
                        "liked" => (int) $reelLiked,
                        "likes" => (int) $reel['likes'],
                        "comments" => (int) $reel['comments'],
                        "views" => (int) $reel['viewed'],
                        "time" => (int) $reel['time'],
                        "ranking" => $ranking,
                        // ADD
                        "is_following" => $isFollowing,
                        "type" => $reel['type'],
                        "duration" => (int) $reel['duration'],
                        "sound_start" => (int) $reel['sound_start'],
                        "hashtag" => $reel['hashtag'],
                        "trending" => (int) $reel['trending'],
                        "show_likes" => (int) $reel['show_likes'],
                        "can_comment" => (int) $reel['can_comment'],
                        "can_duet" => (int) $reel['can_duet'],
                        "can_save" => (int) $reel['can_save'],
                        "can_download" => (int) $reel['can_download'],
                        "sponsored" => isset($reel['sponsored']) ? $reel['sponsored'] : null,
                        "custom_sponsor" => isset($reel['custom_sponsor']) ? $reel['custom_sponsor'] : null,
                        "sponsor_name" => isset($reel['sponsor_name']) ? $reel['sponsor_name'] : null,
                        "sponsor_image" => isset($reel['sponsor_image']) ? $reel['sponsor_image'] : null,
                        "sponsor_link" => isset($reel['sponsor_link']) ? $reel['sponsor_link'] : null,
                        "sponsor_text" => isset($reel['sponsor_text']) ? $reel['sponsor_text'] : null,
                        "saves" => isset($reel['saves']) ? (int) $reel['saves'] : 0,
                        "saved" => $isSaved,
                        "shares" => isset($reel['shares']) ? (int) $reel['shares'] : 0,
                        "is_original" => isset($reel['is_original']) ? $reel['is_original'] : null,
                        "audio_id" => isset($reel['audio_id']) ? $reel['audio_id'] : null,
                        "audio_link" => isset($reel['audio_link']) ? $reel['audio_link'] : null,
                        "audio_cover" => isset($reel['audio_cover']) ? $reel['audio_cover'] : null,
                        "singer" => isset($reel['singer']) ? $reel['singer'] : null,
                        "song_title" => isset($reel['song_title']) ? $reel['song_title'] : null,
                        "is_repost" => isset($reel['is_repost']) ? $reel['is_repost'] : null,
                        "is_ai_generated" => isset($reel['is_ai_generated']) ? $reel['is_ai_generated'] : null,
                        "full_video_link" => isset($reel['full_video_link']) ? $reel['full_video_link'] : null,
                    );
                }
            } else {
                $arr['result'] = 'NORESULTS';
            }

            echo json_encode($arr);
            break;

        case 'reelLike':
            $arr = array();
            $user = secureEncode($_GET['user']);
            $reel = secureEncode($_GET['rid']);
            $motive = secureEncode($_GET['motive']);
            if ($motive == 'like') {
                $cols = 'rid,uid,time';
                $vals = $reel . ',' . $user . ',' . time();
                insertData('reels_likes', $cols, $vals);
            } else {
                $delete = 'WHERE rid = ' . $reel . ' AND uid = ' . $user;
                deleteData('reels_likes', $delete);
            }

            $count = getData('reels', 'likes', "WHERE id = {$reel}");

            if ($motive == 'remove') {
                $count--;
            } else {
                $count++;
            }
            updateData('reels', 'likes', $count, "WHERE id = {$reel}");

            $arr['OK'] = 'Yes';
            echo json_encode($arr);
            break;

        case 'reelSave':
            $arr = [];
            $user = secureEncode($_GET['user']);
            $reel = secureEncode($_GET['rid']);
            $checkLiked = selectC('reels_likes', "WHERE rid = {$reel} AND uid = {$user}");
            $count = getData('reels', 'likes', "WHERE id = {$reel}");
            if ($checkLiked == 0) {
                $cols = 'rid,uid,time';
                $vals = $reel . ',' . $user . ',' . time();
                insertData('reels_likes', $cols, $vals);
                $count++;
                updateData('reels', 'likes', $count, "WHERE id = {$reel}");
            } else {
                $delete = "WHERE rid = {$reel} AND uid = {$user}";
                deleteData('reels_likes', $delete);
                $count--;
                updateData('reels', 'likes', $count, "WHERE id = {$reel}");
            }

            $arr['OK'] = 'Yes';
            echo json_encode($arr);
            break;

        case 'reelShare':
            $arr = [];
            $user = secureEncode($_GET['user']);
            $reel = secureEncode($_GET['rid']);
            $checkLiked = selectC('reels_shares', "WHERE rid = {$reel} AND uid = {$user}");
            $count = getData('reels', 'shares', "WHERE id = {$reel}");
            if ($checkLiked == 0) {
                $cols = 'rid,uid,time';
                $vals = $reel . ',' . $user . ',' . time();
                insertData('reels_shares', $cols, $vals);
                $count++;
                updateData('reels', 'shares', $count, "WHERE id = {$reel}");
            } else {
                $delete = "WHERE rid = {$reel} AND uid = {$user}";
                deleteData('reels_shares', $delete);
                $count--;
                updateData('reels', 'shares', $count, "WHERE id = {$reel}");
            }

            $arr['OK'] = 'Yes';
            echo json_encode($arr);
            break;

        case 'loadDynamicReelsHash':
            $arr = [];
            $order = 'id ASC';
            $uid = (int) secureEncode($_GET['uid']);
            $limit = (int) secureEncode($_GET['limit']);
            $customFilter = secureEncode($_GET['customFilter']);
            $hashtag = secureEncode($_GET['hashtag']);
            $trending = (int) secureEncode($_GET['trending']);
            $isDating = isset($_GET['isDating']) && (int) secureEncode($_GET['isDating']) == 1;

            // Get user info once
            getUserInfo($uid, 0);

            // Prepare base filter
            $filter = 'WHERE visible = 1 AND hashtag LIKE "%' . $hashtag . '%"';

            // Add dating filter if needed
            if ($isDating) {
                $looking = (int) getData('users', 's_gender', "WHERE id = {$uid}");
                $filter .= " AND gender = {$looking} AND uid <> {$sm['user']['id']}";
            }

            $reels = [];

            // Handle different filter types
            if (empty($customFilter) || $customFilter == 'all') {

                // Set order for trending content
                if ($trending === 1) {
                    $order = 'likes DESC';
                }

                // Use string interpolation for LIMIT clause
                $reels = getArray('reels', $filter, $order, "LIMIT {$limit}");
            } else {
                // Handle custom filters
                switch ($customFilter) {
                    case 'liked':
                        $data = getArray('reels_likes', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'purchased':
                        $data = getArray('users_reels_purchases', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'me':
                        $filter = "WHERE visible = 1 AND uid = {$uid}";
                        $reels = getArray('reels', $filter, 'id DESC', '');
                        break;
                }
            }

            // Process results
            if (!empty($reels)) {
                $arr['reels'] = [];

                foreach ($reels as $reel) {
                    $reelId = (int) $reel['id'];
                    $reelUid = (int) $reel['uid'];

                    // Get user data efficiently
                    $userData = getDataArray('users', "id = {$reelUid}");
                    $userRank = getDataArray('users_ranking', "uid = {$reelUid}");
                    $isFollowing = selectC('user_followers', "WHERE u2 = {$reelUid} AND u1 = {$sm['user']['id']}");
                    $isSaved = selectC('reels_saves', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");

                    // Handle username fallback
                    $username = $userData['username'];
                    if (is_numeric($username)) {
                        $username = $userData['name'];
                    }

                    // Get profile photo
                    $profile_photo = profilePhoto($reelUid);

                    // Check if reel is liked
                    $checkLiked = getData('reels_likes', 'rid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                    $reelLiked = ($checkLiked !== 'noData') ? 1 : 0;

                    // Check if reel is purchased
                    $purchased = 'No';
                    if ($reel['reel_price'] > 0) {
                        $checkPurchased = getData('users_reels_purchases', 'uid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                        if ($checkPurchased !== 'noData' || $reelUid == $uid) {
                            $purchased = 'Yes';
                        }
                    } else if ($reelUid == $uid) {
                        $purchased = 'Yes';
                    }

                    // Check for stories
                    $storyFrom = $sm['plugins']['story']['days'];
                    $time = time();
                    $extra = 86400 * $storyFrom;
                    $storyFrom = $time - $extra;
                    $storiesFilter = "WHERE uid = {$reelUid} AND storyTime > {$storyFrom} AND deleted = 0";
                    $openStory = selectC('users_story', $storiesFilter);

                    // Build reel data array
                    $arr['reels'][] = array(
                        "id" => $reelId,
                        "price" => (int) $reel['reel_price'],
                        "play" => $reel['reel_src'],
                        "cover" => $reel['reel_cover'],
                        "caption" => $reel['reel_meta'],
                        "photo" => $profile_photo,
                        "photo_big" => profilePhoto($reelUid, 1),
                        "purchased" => $purchased,
                        "user_id" => $reelUid,
                        "username" => $username,
                        "name" => $userData['name'],
                        "age" => $userData['age'],
                        "city" => $userData['city'],
                        "liked" => $reelLiked,
                        "likes" => (int) $reel['likes'],
                        "comments" => (int) $reel['comments'],
                        "views" => (int) $reel['viewed'],
                        "time" => (int) $reel['time'],
                        "ranking" => (int) $userRank['rank_type'],
                        // ADD
                        "is_following" => $isFollowing,
                        "type" => $reel['type'],
                        "duration" => (int) $reel['duration'],
                        "sound_start" => (int) $reel['sound_start'],
                        "hashtag" => $reel['hashtag'],
                        "trending" => (int) $reel['trending'],
                        "show_likes" => (int) $reel['show_likes'],
                        "can_comment" => (int) $reel['can_comment'],
                        "can_duet" => (int) $reel['can_duet'],
                        "can_save" => (int) $reel['can_save'],
                        "can_download" => (int) $reel['can_download'],
                        "sponsored" => isset($reel['sponsored']) ? $reel['sponsored'] : null,
                        "custom_sponsor" => isset($reel['custom_sponsor']) ? $reel['custom_sponsor'] : null,
                        "sponsor_name" => isset($reel['sponsor_name']) ? $reel['sponsor_name'] : null,
                        "sponsor_image" => isset($reel['sponsor_image']) ? $reel['sponsor_image'] : null,
                        "sponsor_link" => isset($reel['sponsor_link']) ? $reel['sponsor_link'] : null,
                        "sponsor_text" => isset($reel['sponsor_text']) ? $reel['sponsor_text'] : null,
                        "saves" => isset($reel['saves']) ? (int) $reel['saves'] : 0,
                        "saved" => $isSaved,
                        "shares" => isset($reel['shares']) ? (int) $reel['shares'] : 0,
                        "is_original" => isset($reel['is_original']) ? $reel['is_original'] : null,
                        "audio_id" => isset($reel['audio_id']) ? $reel['audio_id'] : null,
                        "audio_link" => isset($reel['audio_link']) ? $reel['audio_link'] : null,
                        "audio_cover" => isset($reel['audio_cover']) ? $reel['audio_cover'] : null,
                        "singer" => isset($reel['singer']) ? $reel['singer'] : null,
                        "song_title" => isset($reel['song_title']) ? $reel['song_title'] : null,
                        "is_repost" => isset($reel['is_repost']) ? $reel['is_repost'] : null,
                        "is_ai_generated" => isset($reel['is_ai_generated']) ? $reel['is_ai_generated'] : null,
                        "full_video_link" => isset($reel['full_video_link']) ? $reel['full_video_link'] : null,
                    );
                }
            } else {
                $arr['result'] = 'NORESULTS';
            }

            echo json_encode($arr);
            break;

        case 'loadDynamicReelsFollowing':
            $arr = [];
            $order = 'id ASC';
            $uid = (int) secureEncode($_GET['uid']);
            $limit = (int) secureEncode($_GET['limit']);
            $customFilter = secureEncode($_GET['customFilter']);
            $trending = (int) secureEncode($_GET['trending']);
            $isDating = isset($_GET['isDating']) && (int) secureEncode($_GET['isDating']) == 1;

            if (!isset($sm['user'])) {
                getUserInfo($uid, 0);
            }
            $cFilter = 'WHERE u1 = ' . $uid;
            $isGetMore = isset($_GET['getMore']) ? secureEncode($_GET['getMore']) : 0;
            $lastTime = isset($_GET['lastTime']) ? secureEncode($_GET['lastTime']) : 999999999;
            $limit = 50;
            $u_total = $mysqli->query("SELECT u2 FROM user_followers WHERE u1 = '" . $uid . "' AND u2 < '" . $lastTime . "' ORDER BY id DESC LIMIT $limit ");
            $array = [];
            if ($u_total->num_rows > 0) {
                while ($u_t = $u_total->fetch_object()) {
                    $array[] = $u_t->u2;
                }
            }
            // Prepare base filter
            $filter = 'WHERE visible = 1 AND uid IN (' . implode(',', $array) . ')';

            $reels = [];

            // Handle different filter types
            if (empty($customFilter) || $customFilter == 'all') {
                // Apply last viewed filter for non-trending content
                if ($trending === 0) {
                    $lastViewedReel = getData('users_reels_played', 'rid', "WHERE uid = {$sm['user']['id']}");
                    if ($lastViewedReel !== 'noData') {
                        $filter .= " AND id > {$lastViewedReel}";
                    }
                }

                // Set order for trending content
                if ($trending === 1) {
                    $order = 'likes DESC';
                }

                // Use string interpolation for LIMIT clause
                $reels = getArray('reels', $filter, $order, "LIMIT {$limit}");
            } else {
                // Handle custom filters
                switch ($customFilter) {
                    case 'liked':
                        $data = getArray('reels_likes', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'purchased':
                        $data = getArray('users_reels_purchases', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'me':
                        $filter = "WHERE visible = 1 AND uid = {$uid}";
                        $reels = getArray('reels', $filter, 'id DESC', '');
                        break;
                }
            }

            // Process results
            if (!empty($reels)) {
                $arr['reels'] = [];

                foreach ($reels as $reel) {
                    $reelId = (int) $reel['id'];
                    $reelUid = (int) $reel['uid'];

                    // Get user data efficiently
                    $userData = getDataArray('users', "id = {$reelUid}");
                    $userRank = getDataArray('users_ranking', "uid = {$reelUid}");
                    $isFollowing = selectC('user_followers', "WHERE u2 = {$reelUid} AND u1 = {$sm['user']['id']}");
                    $isSaved = selectC('reels_saves', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");

                    // Handle username fallback
                    $username = $userData['username'];
                    if (is_numeric($username)) {
                        $username = $userData['name'];
                    }

                    // Get profile photo
                    $profile_photo = profilePhoto($reelUid);

                    // Check if reel is liked
                    $checkLiked = getData('reels_likes', 'rid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                    $reelLiked = ($checkLiked !== 'noData') ? 1 : 0;

                    // Check if reel is purchased
                    $purchased = 'No';
                    if ($reel['reel_price'] > 0) {
                        $checkPurchased = getData('users_reels_purchases', 'uid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                        if ($checkPurchased !== 'noData' || $reelUid == $uid) {
                            $purchased = 'Yes';
                        }
                    } else if ($reelUid == $uid) {
                        $purchased = 'Yes';
                    }

                    // Check for stories
                    $storyFrom = $sm['plugins']['story']['days'];
                    $time = time();
                    $extra = 86400 * $storyFrom;
                    $storyFrom = $time - $extra;
                    $storiesFilter = "WHERE uid = {$reelUid} AND storyTime > {$storyFrom} AND deleted = 0";
                    $openStory = selectC('users_story', $storiesFilter);

                    // Build reel data array
                    $arr['reels'][] = array(
                        "id" => $reelId,
                        "price" => (int) $reel['reel_price'],
                        "play" => $reel['reel_src'],
                        "cover" => $reel['reel_cover'],
                        "caption" => $reel['reel_meta'],
                        "photo" => $profile_photo,
                        "photo_big" => profilePhoto($reelUid, 1),
                        "purchased" => $purchased,
                        "user_id" => $reelUid,
                        "username" => $username,
                        "name" => $userData['name'],
                        "age" => (int) $userData['age'],
                        "city" => $userData['city'],
                        "liked" => (int) $reelLiked,
                        "likes" => (int) $reel['likes'],
                        "comments" => (int) $reel['comments'],
                        "views" => (int) $reel['viewed'],
                        "time" => (int) $reel['time'],
                        "ranking" => $userRank['rank_type'],
                        // ADD
                        "is_following" => $isFollowing,
                        "type" => $reel['type'],
                        "duration" => (int) $reel['duration'],
                        "sound_start" => (int) $reel['sound_start'],
                        "hashtag" => $reel['hashtag'],
                        "trending" => (int) $reel['trending'],
                        "show_likes" => (int) $reel['show_likes'],
                        "can_comment" => (int) $reel['can_comment'],
                        "can_duet" => (int) $reel['can_duet'],
                        "can_save" => (int) $reel['can_save'],
                        "can_download" => (int) $reel['can_download'],
                        "sponsored" => isset($reel['sponsored']) ? $reel['sponsored'] : null,
                        "custom_sponsor" => isset($reel['custom_sponsor']) ? $reel['custom_sponsor'] : null,
                        "sponsor_name" => isset($reel['sponsor_name']) ? $reel['sponsor_name'] : null,
                        "sponsor_image" => isset($reel['sponsor_image']) ? $reel['sponsor_image'] : null,
                        "sponsor_link" => isset($reel['sponsor_link']) ? $reel['sponsor_link'] : null,
                        "sponsor_text" => isset($reel['sponsor_text']) ? $reel['sponsor_text'] : null,
                        "saves" => isset($reel['saves']) ? (int) $reel['saves'] : 0,
                        "saved" => $isSaved,
                        "shares" => isset($reel['shares']) ? (int) $reel['shares'] : 0,
                        "is_original" => isset($reel['is_original']) ? $reel['is_original'] : null,
                        "audio_id" => isset($reel['audio_id']) ? $reel['audio_id'] : null,
                        "audio_link" => isset($reel['audio_link']) ? $reel['audio_link'] : null,
                        "audio_cover" => isset($reel['audio_cover']) ? $reel['audio_cover'] : null,
                        "singer" => isset($reel['singer']) ? $reel['singer'] : null,
                        "song_title" => isset($reel['song_title']) ? $reel['song_title'] : null,
                        "is_repost" => isset($reel['is_repost']) ? $reel['is_repost'] : null,
                        "is_ai_generated" => isset($reel['is_ai_generated']) ? $reel['is_ai_generated'] : null,
                        "full_video_link" => isset($reel['full_video_link']) ? $reel['full_video_link'] : null,
                    );
                }
            } else {
                $arr['result'] = 'NORESULTS';
            }

            echo json_encode($arr);
            break;

        case 'searchDynamicReels':
            $arr = [];
            $order = 'id ASC';
            $uid = (int) secureEncode($_GET['uid']);
            $limit = (int) secureEncode($_GET['limit']);
            $customFilter = secureEncode($_GET['customFilter']);
            $query = secureEncode($_GET['query']);
            $trending = (int) secureEncode($_GET['trending']);
            $isDating = isset($_GET['isDating']) && (int) secureEncode($_GET['isDating']) == 1;

            // Get user info once
            getUserInfo($uid, 0);

            // Prepare base filter
            $filter = 'WHERE visible = 1 AND reel_meta LIKE "%' . $query . '%" OR hashtag LIKE "%' . $query . '%" OR singer LIKE "%' . $query . '%" OR song_title LIKE "%' . $query . '%"';

            // Add dating filter if needed
            if ($isDating) {
                $looking = (int) getData('users', 's_gender', "WHERE id = {$uid}");
                $filter .= " AND gender = {$looking} AND uid <> {$sm['user']['id']}";
            }

            $reels = [];

            // Handle different filter types
            if (empty($customFilter) || $customFilter == 'all') {
                // Set order for trending content
                if ($trending === 1) {
                    $order = 'likes DESC';
                }

                // Use string interpolation for LIMIT clause
                $reels = getArray('reels', $filter, $order, "LIMIT {$limit}");
            } else {
                // Handle custom filters
                switch ($customFilter) {
                    case 'liked':
                        $data = getArray('reels_likes', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'purchased':
                        $data = getArray('users_reels_purchases', "WHERE uid = {$sm['user']['id']}", 'time DESC', 'LIMIT 0,300');
                        if (!empty($data)) {
                            foreach ($data as $d) {
                                $rid = (int) $d['rid'];
                                $reelData = getDataArray('reels', "id = {$rid}");
                                if ($reelData) {
                                    $reels[] = $reelData;
                                }
                            }
                        }
                        break;

                    case 'me':
                        $filter = "WHERE visible = 1 AND uid = {$uid}";
                        $reels = getArray('reels', $filter, 'id DESC', '');
                        break;
                }
            }

            // Process results
            if (!empty($reels)) {
                $arr['reels'] = [];

                foreach ($reels as $reel) {
                    $reelId = (int) $reel['id'];
                    $reelUid = (int) $reel['uid'];

                    // Get user data efficiently
                    $userData = getDataArray('users', "id = {$reelUid}");
                    $userRank = getDataArray('users_ranking', "uid = {$reelUid}");
                    $isFollowing = selectC('user_followers', "WHERE u2 = {$reelUid} AND u1 = {$sm['user']['id']}");
                    $isSaved = selectC('reels_saves', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");

                    // Handle username fallback
                    $username = $userData['username'];
                    if (is_numeric($username)) {
                        $username = $userData['name'];
                    }

                    // Get profile photo
                    $profile_photo = profilePhoto($reelUid);

                    // Check if reel is liked
                    $checkLiked = getData('reels_likes', 'rid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                    $reelLiked = ($checkLiked !== 'noData') ? 1 : 0;

                    // Check if reel is purchased
                    $purchased = 'No';
                    if ($reel['reel_price'] > 0) {
                        $checkPurchased = getData('users_reels_purchases', 'uid', "WHERE rid = {$reelId} AND uid = {$sm['user']['id']}");
                        if ($checkPurchased !== 'noData' || $reelUid == $uid) {
                            $purchased = 'Yes';
                        }
                    } else if ($reelUid == $uid) {
                        $purchased = 'Yes';
                    }

                    // Check for stories
                    $storyFrom = $sm['plugins']['story']['days'];
                    $time = time();
                    $extra = 86400 * $storyFrom;
                    $storyFrom = $time - $extra;
                    $storiesFilter = "WHERE uid = {$reelUid} AND storyTime > {$storyFrom} AND deleted = 0";
                    $openStory = selectC('users_story', $storiesFilter);

                    // Build reel data array
                    $arr['reels'][] = array(
                        "id" => $reelId,
                        "price" => (int) $reel['reel_price'],
                        "play" => $reel['reel_src'],
                        "cover" => $reel['reel_cover'],
                        "caption" => $reel['reel_meta'],
                        "photo" => $profile_photo,
                        "photo_big" => profilePhoto($reelUid, 1),
                        "purchased" => $purchased,
                        "user_id" => $reelUid,
                        "username" => $username,
                        "name" => $userData['name'],
                        "age" => $userData['age'],
                        "city" => $userData['city'],
                        "liked" => $reelLiked,
                        "likes" => (int) $reel['likes'],
                        "comments" => (int) $reel['comments'],
                        "views" => (int) $reel['viewed'],
                        "time" => (int) $reel['time'],
                        "ranking" => (int) $userRank['rank_type'],
                        // ADD
                        "is_following" => $isFollowing,
                        "type" => $reel['type'],
                        "duration" => (int) $reel['duration'],
                        "sound_start" => (int) $reel['sound_start'],
                        "hashtag" => $reel['hashtag'],
                        "trending" => (int) $reel['trending'],
                        "show_likes" => (int) $reel['show_likes'],
                        "can_comment" => (int) $reel['can_comment'],
                        "can_duet" => (int) $reel['can_duet'],
                        "can_save" => (int) $reel['can_save'],
                        "can_download" => (int) $reel['can_download'],
                        "sponsored" => isset($reel['sponsored']) ? $reel['sponsored'] : null,
                        "custom_sponsor" => isset($reel['custom_sponsor']) ? $reel['custom_sponsor'] : null,
                        "sponsor_name" => isset($reel['sponsor_name']) ? $reel['sponsor_name'] : null,
                        "sponsor_image" => isset($reel['sponsor_image']) ? $reel['sponsor_image'] : null,
                        "sponsor_link" => isset($reel['sponsor_link']) ? $reel['sponsor_link'] : null,
                        "sponsor_text" => isset($reel['sponsor_text']) ? $reel['sponsor_text'] : null,
                        "saves" => isset($reel['saves']) ? (int) $reel['saves'] : 0,
                        "saved" => $isSaved,
                        "shares" => isset($reel['shares']) ? (int) $reel['shares'] : 0,
                        "is_original" => isset($reel['is_original']) ? $reel['is_original'] : null,
                        "audio_id" => isset($reel['audio_id']) ? $reel['audio_id'] : null,
                        "audio_link" => isset($reel['audio_link']) ? $reel['audio_link'] : null,
                        "audio_cover" => isset($reel['audio_cover']) ? $reel['audio_cover'] : null,
                        "singer" => isset($reel['singer']) ? $reel['singer'] : null,
                        "song_title" => isset($reel['song_title']) ? $reel['song_title'] : null,
                        "is_repost" => isset($reel['is_repost']) ? $reel['is_repost'] : null,
                        "is_ai_generated" => isset($reel['is_ai_generated']) ? $reel['is_ai_generated'] : null,
                        "full_video_link" => isset($reel['full_video_link']) ? $reel['full_video_link'] : null,
                    );
                }
            } else {
                $arr['result'] = 'NORESULTS';
            }

            echo json_encode($arr);
            break;

        case 'viewed':
            $rid = secureEncode($_GET['rid']);
            $uid = secureEncode($_GET['uid']);
            $fromTrending = secureEncode($_GET['from_trending']);
            $time = time();
            $arr = array();
            $arr['result'] = 'OK';
            if ($fromTrending == 0) {
                $query = "INSERT INTO users_reels_played (uid,rid,time) VALUES ('" . $uid . "', '" . $rid . "', '" . $time . "') ON DUPLICATE KEY UPDATE rid = '" . $rid . "',time = " . $time;
                $mysqli->query($query);
            }
            updateData('reels', 'viewed', 'viewed + 1', 'WHERE id =' . $rid);

            echo json_encode($arr);
            break;

        case 'removeReel':
            $id = secureEncode($_GET['rid']);
            $uid = secureEncode($_GET['uid']);
            $arr = array();
            $time = time();
            deleteData('reels', 'WHERE uid = ' . $uid . ' AND id = ' . $id);
            deleteData('reels_likes', 'WHERE rid = ' . $id);
            deleteData('users_reels_played', 'WHERE rid = ' . $id);
            deleteData('users_reels_purchases', 'WHERE rid = ' . $id);
            $arr['OK'] = 'OK';
            echo json_encode($arr);
            break;

        case 'loadReelComments':
            $fid = secureEncode($_GET['fid']);
            $uid = secureEncode($_GET['uid']);
            $arr = array();
            $cFilter = 'WHERE rid = ' . $fid;
            $comments = getArray('reel_comments', $cFilter, 'time DESC', 'LIMIT 0,65');
            foreach ($comments as $c) {
                $c['photo'] = profilePhoto($c['uid']);
                $c['username'] = getData('users', 'username', 'WHERE id = ' . $c['uid']);
                $c['liked'] = getData('reels_comments_likes', 'liked', 'WHERE cid = ' . $c['id'] . ' AND uid = ' . $uid);
                $c['likes'] = selectC('reels_comments_likes', 'WHERE cid = ' . $c['id']);
                $arr[] = $c;
            }
            echo json_encode($arr);
            break;

        case 'commentReel':
            $arr = array();
            $user = secureEncode($_GET['user']);
            $feed = secureEncode($_GET['fid']);
            $motive = secureEncode($_GET['motive']);
            $comment = secureEncode($_GET['comment']);

            if ($motive == 'comment') {
                $cols = 'rid,uid,comment,time';
                $vals = $feed . ',' . $user . ',"' . $comment . '",' . time();
                insertData('reel_comments', $cols, $vals);
            }

            $arr['OK'] = 'Yes';
            echo json_encode($arr);
            break;

        case 'like_comment':
            $query = secureEncode($_GET['query']);
            $data = explode(',', $query);
            $time = time();
            $uid = $data[0];
            $cid = $data[1];
            $like = $data[2];
            $time = time();
            $checkLiked = selectC('reels_comments_likes', "WHERE cid = {$cid} AND uid = {$uid}");
            if ($checkLiked == 0) {
                $query = "INSERT INTO reels_comments_likes (cid,uid,time,liked) VALUES ('" . $cid . "', '" . $uid . "', '" . $time . "', '" . $like . "') ON DUPLICATE KEY UPDATE liked = '" . $like . "'";
            } else {
                $query = "DELETE FROM reels_comments_likes WHERE cid = '" . $cid . "' AND uid = '" . $uid . "'";
            }

            $mysqli->query($query);
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'uploadReel':
            $time = time();
            $arr = array();
            $arr['uploaded'] = 'OK';
            $uid = secureEncode($_POST['uid']);
            $gender = secureEncode($_POST['gender']);
            $price = secureEncode($_POST['price']);
            $caption = secureEncode($_POST['caption']);
            $path = secureEncode($_POST['path']);

            $cols = 'uid,reel_price,reel_src,reel_meta,time,visible,gender';
            $vals = $uid . ',' . $price . ',"' . $path . '","' . $caption . '","' . $time . '",1,' . $gender;
            insertData('reels', $cols, $vals);

            echo json_encode($arr);
            break;

        case 'editReel':
            $time = time();
            $arr = array();
            $arr['edited'] = 'OK';
            $reel = secureEncode($_POST['reel']);
            $price = secureEncode($_POST['price']);
            $caption = secureEncode($_POST['caption']);

            $query = "UPDATE reels SET reel_meta = '" . $caption . "',reel_price = " . $price . " WHERE id = " . $reel;
            $mysqli->query($query);

            echo json_encode($arr);
            break;

        case 'reelLike':
            $arr = array();
            $user = secureEncode($_GET['user']);
            $reel = secureEncode($_GET['rid']);
            $motive = secureEncode($_GET['motive']);
            if ($motive == 'like') {
                $cols = 'rid,uid,time';
                $vals = $reel . ',' . $user . ',' . time();
                insertData('reels_likes', $cols, $vals);
            } else {
                $delete = 'WHERE rid = ' . $reel . ' AND uid = ' . $user;
                deleteData('reels_likes', $delete);
            }

            $count = getData('reels', 'likes', "WHERE id = {$reel}");

            if ($motive == 'remove') {
                $count--;
            } else {
                $count++;
            }
            updateData('reels', 'likes', $count, "WHERE id = {$reel}");

            $arr['OK'] = 'Yes';
            echo json_encode($arr);
            break;

        case 'reelSave':
            $arr = [];
            $user = secureEncode($_GET['user']);
            $reel = secureEncode($_GET['rid']);
            $checkLiked = selectC('reels_likes', "WHERE rid = {$reel} AND uid = {$user}");
            $count = getData('reels', 'likes', "WHERE id = {$reel}");
            if ($checkLiked == 0) {
                $cols = 'rid,uid,time';
                $vals = $reel . ',' . $user . ',' . time();
                insertData('reels_likes', $cols, $vals);
                $count++;
                updateData('reels', 'likes', $count, "WHERE id = {$reel}");
            } else {
                $delete = "WHERE rid = {$reel} AND uid = {$user}";
                deleteData('reels_likes', $delete);
                $count--;
                updateData('reels', 'likes', $count, "WHERE id = {$reel}");
            }

            $arr['OK'] = 'Yes';
            echo json_encode($arr);
            break;

        case 'reelShare':
            $arr = [];
            $user = secureEncode($_GET['user']);
            $reel = secureEncode($_GET['rid']);
            $checkLiked = selectC('reels_shares', "WHERE rid = {$reel} AND uid = {$user}");
            $count = getData('reels', 'shares', "WHERE id = {$reel}");
            if ($checkLiked == 0) {
                $cols = 'rid,uid,time';
                $vals = $reel . ',' . $user . ',' . time();
                insertData('reels_shares', $cols, $vals);
                $count++;
                updateData('reels', 'shares', $count, "WHERE id = {$reel}");
            } else {
                $delete = "WHERE rid = {$reel} AND uid = {$user}";
                deleteData('reels_shares', $delete);
                $count--;
                updateData('reels', 'shares', $count, "WHERE id = {$reel}");
            }

            $arr['OK'] = 'Yes';
            echo json_encode($arr);
            break;

        case 'purchase_reel':
            $arr = [];
            $time = time();
            $user = secureEncode($_POST['user']);
            $reel = secureEncode($_POST['rid']);
            $action = secureEncode($_POST['purchase_action']);

            if ($action == 'purchase') {
                $cols = 'uid,rid,time';
                $vals = $uid . ',' . $reel . ',"' . $time . '"';
                insertData('users_reels_purchases', $cols, $vals);
                updateData('reels', 'purchased', 'purchased +1', 'WHERE id =' . $reel);
            } else {
                deleteData('users_reels_purchases', 'WHERE uid = ' . $uid . ' AND rid = ' . $reel);
            }
            $arr['OK'] = 'OK';
            echo json_encode($arr);
            break;

        default:
            break;
    }
}
