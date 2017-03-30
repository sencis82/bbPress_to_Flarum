<?php
set_time_limit(0);
header("Content-Type: text/html;charset=UTF-8");
// Configs for phpbb database
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "mydbname";
$fileName = "flarum_posts.sql";
$phpprefix = "bb_";
$post_data = "INSERT INTO posts (id, user_id, discussion_id, time, type, content, number, ip_address, is_approved) VALUES \n";
$diss_data = "INSERT INTO discussions_tags (discussion_id, tag_id) VALUES \n";
$myfile = file_exists($fileName) ? fopen($fileName, 'a') : fopen($fileName, 'w');
// Create connection
$conn = new mysqli($servername, $username, $password,$dbname);

printf("Initial character set: %s\n", $conn->character_set_name());

/* change character set to utf8 */
if (!$conn->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $conn->error);
    exit();
} else {
    printf("Current character set: %s\n", $conn->character_set_name());
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

//Generate Disscussions

//poster_id
echo "<hr/>";
$result = $conn->query("SELECT * FROM ".$phpprefix."topics WHERE topic_status=0");
if ($result->num_rows > 0) {
        fwrite($myfile, "INSERT INTO discussions (id, title, start_time, comments_count, number_index, participants_count, start_post_id, last_post_id, start_user_id, last_user_id, last_time, slug, is_approved, is_locked, is_sticky ) VALUES \n");
        $tmp_str = "";
	    $p_count = 0;
		while($row = $result->fetch_assoc()) {
			$posts = $conn->query("SELECT * FROM ".$phpprefix."posts where topic_id=".$row["topic_id"]." ORDER BY topic_id, post_id ASC");
            echo $posts->num_rows." results \n";
			$array = array();
            $date = new DateTime();
            $tmp_date =  $date->format('Y-m-d H:i:s');
            $p_count = 0;
			while($tpl = $posts->fetch_assoc()) {
				$array[] = $tpl;
				$date->$tpl["post_time"];
				$tmp_date =  $date->format('Y-m-d H:i:s');
	            $cleanComment = "";
                if($p_count>0){$cleanComment = "<r><p>".textProcessing($conn,$tpl['post_text'])."</p></r>";}
	            else{$cleanComment = "<t><p>".textProcessing($conn,$tpl['post_text'])."</p></t>";}

	            $p_count ++;
                $post_status = $tpl["post_status"]==0 ? 1 : 0;

				$post_data .= " (".$tpl['post_id'].", ".$tpl['poster_id'].", ".$row['topic_id'].", '".$tpl["post_time"]."', 'comment', '".$cleanComment."', ".$p_count.",".$p_count.", '".$tpl['poster_ip']."', ".$post_status."), \n";
			}
			$date = new DateTime();
			$date->$row["topic_start_time"];
			$tmp_date =  $date->format('Y-m-d H:i:s');
			$last_date = new DateTime();
			$last_date->$row["topic_time"];
			$tmp_last_date =  $last_date->format('Y-m-d H:i:s');
            $topic_status = $row["topic_status"]==0 ? 1 : 0;
            $topic_open = $row["topic_open"]==1 ? 0 : 1;

			$diss_data.="(".$row["topic_id"].", ".$row["forum_id"]."),";
			$tmp_str .= " (".$row["topic_id"].", '".textProcessing($conn,$row["topic_title"])."', '".$row["topic_start_time"]."', ".$p_count.", 0, ".$array[0]['post_id'].",".$array[($p_count-1)]['post_id'].", ".$array[0]['poster_id'].", ".($array[($p_count-1)]['poster_id']).", '".$row["topic_time"]."', '".$row["topic_slug"]."',".$topic_status.", ".$topic_open.", ".$row["topic_sticky"]."), \n";
			#$tmp_str = (rtrim($tmp_str,','))."; \n";
			#fwrite($myfile, $tmp_str);
		}
        $tmp_str = (rtrim($tmp_str,','))."; \n";
        fwrite($myfile, $tmp_str);
} else { echo "0 results"; }



//Generate discussions_tags

$diss_data = (rtrim($diss_data,','))."; \n";
fwrite($myfile, $diss_data);

//Generate posts

$post_data = (rtrim($post_data,','))."; \n";
fwrite($myfile, $post_data);

$conn->close();
// Start of functions
function slugify($text){
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
  $text = trim($text, '-');
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = strtolower($text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  if (empty($text)){return 'n-a';}
  return $text;
}
function rand_color() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}
function mysql_escape_mimic($inp) {
    if(is_array($inp))
        return array_map(__METHOD__, $inp);

    if(!empty($inp) && is_string($inp)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
    }
    return $inp;
}
#function that will process the main comment and format it for flaurm and the database;
function textProcessing($conn,$text){
  $text = preg_replace('#\:\w+#', '', $text);
  $text = bbcode_toHTML($text);
  $text  = str_replace("&quot;","\"",$text );
  $text = stripBBCode($text);
  #$text =  nl2br($text);
  #echo $text."<br/> <hr/> <br/>";
  return $conn->real_escape_string($text);
}
function stripBBCode($text_to_search) {
    $pattern = '|[[\/\!]*?[^\[\]]*?]|si';
    $replace = '';
    return preg_replace($pattern, $replace, $text_to_search);
}

function bbcode_toHTML($bbcode){
  $bbcode = preg_replace('#\[b](.+)\[\/b]#', "<b>$1</b>", $bbcode);
  $bbcode = preg_replace('#\[i](.+)\[\/i]#', "<i>$1</i>", $bbcode);
  $bbcode = preg_replace('#\[u](.+)\[\/u]#', "<u>$1</u>", $bbcode);
  $bbcode = preg_replace('#\[img](.+?)\[\/img]#is', "<img src='$1'\>", $bbcode);
  $bbcode = preg_replace('#\[quote=(.+?)](.+?)\[\/quote]#is', "<QUOTE><i>&gt;</i>$2</QUOTE>", $bbcode);
  $bbcode = preg_replace('#\[code:\w+](.+?)\[\/code:\w+]#is', "<CODE>$1<CODE>", $bbcode);
  $bbcode = preg_replace('#\[\*](.+?)\[\/\*]#is', "<li>$1</li>", $bbcode);
  $bbcode = preg_replace('#\[color=\#\w+](.+?)\[\/color]#is', "$1", $bbcode);
  $bbcode = preg_replace('#\[url=(.+?)](.+?)\[\/url]#is', "<a href='$1'>$2</a>", $bbcode);
  $bbcode = preg_replace('#\[url](.+?)\[\/url]#is', "<a href='$1'>$1</a>", $bbcode);
  $bbcode = preg_replace('#\[list](.+?)\[\/list]#is', "<ul>$1</ul>", $bbcode);
  $bbcode = preg_replace('#\[size=200](.+?)\[\/size]#is', "<h1>$1</h1>", $bbcode);
  $bbcode = preg_replace('#\[size=170](.+?)\[\/size]#is', "<h2>$1</h2>", $bbcode);
  $bbcode = preg_replace('#\[size=150](.+?)\[\/size]#is', "<h3>$1</h3>", $bbcode);
  $bbcode = preg_replace('#\[size=120](.+?)\[\/size]#is', "<h4>$1</h4>", $bbcode);
  $bbcode = preg_replace('#\[size=85](.+?)\[\/size]#is', "<h5>$1</h5>", $bbcode);

  // We prepare the attachment link
  $bbcode = preg_replace_callback('#\[attachment=(.+?),(.+?)]#is', 'prepare_img', $bbcode);

  return $bbcode;
}

function prepare_img($matches) {
    $servername = "localhost";
    $username = "root";
    $password = "password";
    $dbname = "mydbname";
    $phpprefix = "bb_";
    $conn = new mysqli($servername, $username, $password,$dbname);
    if (!$conn->set_charset("utf8")) {
        exit();
    }

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $result = $conn->query("SELECT * FROM ".$phpprefix."attachments where id=".$matches[2]);
			
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $path = floor($row["id"]/1000)."/".$row["id"].".".$row["filename"];
            $attachment_url = "http://forum.mydomain.com/assets/files/".$path;
            $attachment_file = $row["filename"];
        }
        return '<IMG alt="image '.$attachment_file.'" src="'.$attachment_url.'"><s>![</s>image '.$attachment_url.'<e>]('.$attachment_file.')</e></IMG>';
    } else { 
        echo "0 results";
        return "";
    }
    $conn->close();
}
?>
