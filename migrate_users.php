<?php
set_time_limit(0);
header("Content-Type: text/html;charset=UTF-8");
// Configs for phpbb database
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "mydbname";
$fileName = "flarum_users.sql";
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
//Generate Usertables
$result = $conn->query("SELECT ID, user_login, user_email, user_url, user_registered, FROM_UNIXTIME(meta_value) as last_online, IFNULL(d.discussionsCount,0) as discussionsCount, IFNULL(p.postsCount,0) as postsCount
FROM wp_us_users
LEFT OUTER JOIN wp_us_usermeta
ON user_id = ID AND meta_key='last_online'
LEFT JOIN (
	SELECT topic_poster, count(*) as discussionsCount from bb_topics group by topic_poster) d on d.topic_poster = ID
LEFT JOIN (
	SELECT poster_id, count(*) as postsCount from bb_posts group by poster_id) p on p.poster_ID = ID
WHERE spam = 0  
ORDER BY ID asc");
if ($result->num_rows > 0) {
    fwrite($myfile, "INSERT INTO users (id, username, email, password, is_activated, bio, join_time, last_seen_time, discussions_count, comments_count, avatar_path) VALUES \n");
    $tmp_str = "";
    $i = 0;
    while(($row = $result->fetch_assoc()) && ($i<2000)) {
        if($row["user_email"] !=''){
                $tmp_password = password_hash(time(), PASSWORD_BCRYPT);
                $date = new DateTime();
                $date->$row["user_registered"];
                $tmp_date =  $date->format('Y-m-d H:i:s');

                $id = md5( $row["ID"] );
	            $location = substr( $id, 0, 1 ) . '/' . substr( $id, 0, 2 ) . '/' . substr( $id, 0, 3 ) . '/' . $id . '_100.png';
                if ( !file_exists( "assets/avatars/" . $location ) ) {
                    $location = '';
                }

                $tmp_str .= "(".$row["ID"].", '".$row["user_login"]."', '".$row["user_email"]."', '".$tmp_password."', 1, '".$row["user_url"]."', '".$row["user_registered"]."', '".$row["last_online"]."', ".$row["discussionsCount"].", ".$row["postsCount"].", '".$location."'), \n";
        }
        $i++;
    }
        $tmp_str = (rtrim($tmp_str,', \n'))."; \n";
        fwrite($myfile, $tmp_str);
} else { echo "0 users"; }

$conn->close();
?>