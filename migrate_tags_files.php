<?php
set_time_limit(0);
header("Content-Type: text/html;charset=UTF-8");
// Configs for phpbb database
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "mydbname";
$fileName = "flarum_tags_files.sql";
$phpprefix = "bb_";
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

//Generate Tags
echo "<hr/>";
$result = $conn->query("SELECT forum_id, forum_name, forum_desc, forum_slug, forum_order, forum_parent  FROM ".$phpprefix."forums");
if ($result->num_rows > 0) {
        fwrite($myfile, "INSERT INTO tags (id, name, description, slug, color, position, parent_id, discussions_count) VALUES \n");
        $tmp_str = "";
		while($row = $result->fetch_assoc()) {
            $topics = $conn->query("SELECT * FROM ".$phpprefix."topics where topic_status=0 AND forum_id=".$row["forum_id"]);
			$t_count = $topics->num_rows;
			$tmp_str .= "(".$row["forum_id"].", '".mysql_escape_mimic($row["forum_name"])."', '".mysql_escape_mimic(strip_tags(stripBBCode($row["forum_desc"])))."', '".mysql_escape_mimic($row["forum_slug"])."', '".rand_color()."', ".$row["forum_order"].", ".$row["forum_parent"].", ".$t_count."), \n";
		}
        $tmp_str = (rtrim($tmp_str,', \n'))."; \n";
        fwrite($myfile, $tmp_str);
} else { echo "0 results"; }

//Generate attachments

$result = $conn->query("SELECT id, user_id, post_id, filename, mime, size, time FROM ".$phpprefix."attachments");
if ($result->num_rows > 0) {
        fwrite($myfile, "INSERT INTO flagrow_files (id, actor_id, post_id, base_name, path, url, type, size, upload_method, created_at, markdown_string) VALUES \n");
        $tmp_str = "";
		while($row = $result->fetch_assoc()) {
            $date = new DateTime();
			$date->setTimestamp($row["time"]);
			$tmp_date =  $date->format('Y-m-d H:i:s');

            $path = floor($row["id"]/1000)."/".$row["id"].".".$row["filename"];
            $url = "http://forum.mydomain.com/assets/files/".$path;
            $markdown = "![image ".$row["id"].".".$row["filename"]."] (".$url.")";

			$tmp_str .= "(".$row["id"].", ".$row["user_id"].", ".$row["post_id"].", '".$row["filename"]."', '".$path."', '".$url."', '".$row["mime"]."', ".$row["size"].", 'local', '".$tmp_date."', '".$markdown."'), \n";
		}
        $tmp_str = (rtrim($tmp_str,', \n'))."; \n";
        fwrite($myfile, $tmp_str);
} else { echo "0 results"; }

$conn->close();
// Start of functions

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

function stripBBCode($text_to_search) {
    $pattern = '|[[\/\!]*?[^\[\]]*?]|si';
    $replace = '';
    return preg_replace($pattern, $replace, $text_to_search);
}
?>
