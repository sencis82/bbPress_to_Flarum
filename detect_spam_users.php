// user is spamer if topic is marked as spam
UPDATE bb_topics t, wp_us_users u
SET u.spam = 1
WHERE
t.topic_poster = u.ID
AND t.topic_status = 1

// spam if URL duplicates (startimng IDs (700) when spamers started to appear)
UPDATE wp_us_users a, (SELECT `user_url`, COUNT(*) as count_posts FROM `wp_us_users` GROUP BY `user_url`) src 
SET a.spam = 1
WHERE a.user_url = src.user_url
AND src.count_posts > 1
AND a.user_url <> ""
AND a.ID > 700
AND a.user_email not like '%.lv' 
AND a.user_url not like '%.lv'

// exceptional FIX
UPDATE wp_us_users u
SET u.spam = 1
WHERE u.spam = 0 
AND 
(
user_email like '%163.com' 
OR user_url like '%163.com' 
or user_email like '%hotmail.com'
or user_email like '%outlook.com'
or user_email like '%yahoo.com'
or user_email like '%yeah.net'
or user_email like '%qq.com'
or user_email like '%sina.com'
or user_email like '%sohu.com'
or user_email like '%tom.com'
or user_email like '%pubmail886.com'
or user_email like '%21cn.com'
or user_email like '%aol.com'
OR (user_url like '%.com%' and user_email not like '%.lv')
) 
and ID > 800 
and ID <> 20046 
and ID <> 7925
and ID <> 7734
and ID <> 17328
and ID <> 4121
and ID <> 9325
and ID <> 10049
and ID <> 10297
and ID <> 17374
and ID <> 17080
and ID <> 16942
and ID <> 15995
and ID <> 12863
and ID <> 13242
and ID <> 10447
and ID <> 10298
and ID <> 7925
and ID <> 6803
and ID <> 2800

// FIX if user has at least one noSpam topic
UPDATE bb_topics t, wp_us_users u
SET u.spam = 0
WHERE
u.spam = 1
AND t.topic_poster = u.ID
AND t.topic_status = 0

// FIX if user has a post in noSpam topic
UPDATE  bb_topics t, wp_us_users u, bb_posts p
SET u.spam = 0
WHERE
p.poster_id = u.ID
AND t.topic_status = 0
AND p.topic_id = t.topic_id
AND u.spam = 1
