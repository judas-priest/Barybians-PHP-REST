<?php

abstract class Database
{
    public $mysqli;
    public $conn;
    public $ftpLogin;
    public function __construct()
    {
        $this->mysqli = new mysqli(DB_BRB_SERVER, DB_BRB_USERNAME, DB_BRB_PASSWORD, DB_BRB_DATABASE);
        if ($this->mysqli->connect_error) {
            throw new Exception('Database connection error', 500);
            exit();
        }
        $this->mysqli->set_charset('utf8mb4');
    }
    public function __destruct()
    {
        $this->mysqli->close();
    }
    public function IdempotencyValidator(int $id, string $uuid)
    {
        $response = $this->mysqli->query("SELECT `requests`.`request` FROM `requests` WHERE `requests`.`user_id` = $id ORDER BY id DESC LIMIT 1");
        $request =   $response->fetch_row();
        if ($request[0] === $uuid) {
            return true;
        } else {
            $stmt = $this->mysqli->prepare('INSERT INTO `requests` (`id`,`user_id`,`request`) VALUES (NULL,?,?) ON DUPLICATE KEY UPDATE `request`=?');
            $stmt->bind_param('iss', $id, $uuid, $uuid);
            $stmt->execute();
            return false;
        }
    }
    public function ftpConnect()
    {
        $this->conn = ftp_connect('194.63.141.39');
        $this->ftpLogin = ftp_login($this->conn, 'user787022', 'NCkMq0FxEXlR');
    }
    public function LinkPreview(string $url, int $full = 0)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
        return GetLinkPreview($url, $full);
    }

    public function UsersOnline2()
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
        $response = $this->mysqli->query("SELECT DISTINCT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit, user_roles.role_id AS roleId FROM users INNER JOIN user_roles ON users.id=user_roles.user_id");
        $json     = [];
        while ($user = $response->fetch_assoc()) {
            if (online($user['lastVisit'])) {
                $json[] = $user;
            }
        }
        return $json;
    }
    public function UsersOnline()
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
        $response = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users");
        $json     = [];
        while ($user = $response->fetch_assoc()) {
            $user['online'] = online($user['lastVisit']);
            $json[] = $user;
        }
        return $json;
    }
    public function UsersOnlineSelective($users)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
        $response = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE users.id IN ($users)");
        $json     = [];
        while ($user = $response->fetch_assoc()) {
            $user['online']['id']     = $user['id'];
            $user['online']['online'] = online($user['lastVisit']);
            $json[] = $user['online'];
        }
        return $json;
    }
    public function Profile(int $id)
    {
        $response = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit, user_roles.role_id AS roleId, (SELECT COUNT(posts.id) FROM posts WHERE posts.user_id = '$id') as postsCount FROM users, user_roles WHERE users.id='$id' AND user_roles.user_id='$id'");
        $json     = $response->fetch_assoc();
        if ($json == null) throw new Exception('User is not found!', 404);

        $response      = $this->mysqli->query("SELECT posts.id, posts.user_id AS 'userId', posts.title, posts.text, posts.attachment as 'attachments', DATE_FORMAT(posts.time,'%d.%m.%Y') AS date, DATE_FORMAT(posts.time,'%H:%i') AS time, UNIX_TIMESTAMP(posts.time) AS utime, posts.edited, posts.last_edit AS 'lastModified' FROM posts WHERE posts.user_id = '$id' ORDER BY id DESC LIMIT 0, 10");
        $json['posts'] = [];
        while ($post = $response->fetch_assoc()) {
            $post['attachments'] = json_decode($post['attachments']);
            $responseLikes      = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM likedposts INNER JOIN users ON likedposts.user_id = users.id WHERE `post_id` = {$post['id']}");

            $post['likedUsers'] = [];
            while ($like = $responseLikes->fetch_assoc()) $post['likedUsers'][] = $like;

            $post['likesCount'] = count($post['likedUsers']);
            $responseComments   = $this->mysqli->query("SELECT comments.id, comments.post_id AS 'postId', comments.user_id AS 'userId', comments.text, DATE_FORMAT(comments.time,'%d.%m.%Y') AS date, DATE_FORMAT(comments.time,'%H:%i') AS time, UNIX_TIMESTAMP(comments.time) AS utime, comments.edited as 'edited', comments.last_edit AS 'lastModified', comments.attachment as 'attachments' FROM comments  WHERE `post_id` = {$post['id']}");
            $post['comments']   = [];
            while ($comment = $responseComments->fetch_assoc()) {
                $comment['attachments'] = json_decode($comment['attachments']);
                $responseCommentsAuthor = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE id= {$comment['userId']}");
                $comment['author']      = [];

                while ($author = $responseCommentsAuthor->fetch_assoc()) $comment['author'] = $author;

                $post['comments'][] =  $comment;
            }
            $post['commentsCount'] = count($post['comments']);
            $json['posts'][] = $post;
        }
        return $json;
    }
    public function Profile2(int $id)
    {
        /* ПАСТЫ 
        SELECT (JSON_OBJECT('title',posts.title,'userId',posts.user_id,'text',posts.text,'attachments',posts.attachment,'time', DATE_FORMAT(posts.time,'%H:%i:%s'),'date',DATE_FORMAT(posts.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(posts.time),'lastModified',posts.last_edit,'edited',posts.edited,'comments',JSON_ARRAYAGG(JSON_OBJECT('text',comments.text)))) FROM posts INNER JOIN comments ON posts.id=comments.post_id GROUP By posts.id;
        */
        /* sers ?
        SELECT (JSON_OBJECT('id',users.id,'firstName',users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate', DATE_FORMAT(`users`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit', DATE_FORMAT(users.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(users.last_visit),'posts',(SELECT JSON_ARRAYAGG(JSON_OBJECT('title',posts.title,'userId',posts.user_id,'text',posts.text,'attachments',posts.attachment,'time', DATE_FORMAT(posts.time,'%H:%i:%s'),'date',DATE_FORMAT(posts.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(posts.time),'lastModified',posts.last_edit,'edited',posts.edited,'comments',(SELECT JSON_ARRAYAGG(JSON_OBJECT('id',comments.id,'postId',comments.post_id,'text',comments.text,'date',DATE_FORMAT(comments.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(comments.time),'lastModified',comments.last_edit,'edited',comments.edited,'attachments',comments.attachment)) from comments) )) from posts WHERE posts.user_id = users.id LIMIT 0, 10))) FROM users INNER JOIN posts ON users.id = posts.user_id 
INNER JOIN comments ON comments.post_id = posts.id WHERE users.id = 1;
        */
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('id',users.id,'firstName',users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate', DATE_FORMAT(`users`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit', DATE_FORMAT(users.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(users.last_visit),
        'posts', JSON_ARRAYAGG(JSON_OBJECT('title',posts.title,'userId',posts.user_id,'text',posts.text,'attachments',posts.attachment,'time', DATE_FORMAT(posts.time,'%H:%i:%s'),'date',DATE_FORMAT(posts.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(posts.time),'lastModified',posts.last_edit,'edited',posts.edited)))) FROM users  INNER JOIN posts ON users.id = posts.user_id 
        INNER JOIN comments ON comments.post_id = posts.id WHERE users.id=1");

        // return json_decode($json, true);
        return json_decode($response->fetch_row()[0]);
    }
    public function Dialogs(int $id)
    {
        /*
        SELECT (JSON_OBJECT('secondUser',JSON_OBJECT('id',userId,'firstName', firstName,'lastName',lastName,'photo',photo,'status',status,'ubirthDate',ubirthDate,'sex',sex,'ulastVisit',ulastVisit), 'lastMessage',JSON_OBJECT('id',id,'senderId',senderId,'receiverId',receiverId,'text',text,'attachments',attachments,'time',time,'utime',utime,'unread',unread)))
FROM  (SELECT
    messages.id AS id,
    messages.sender_id AS senderId,
    messages.reciever_id AS receiverId,
    users.id AS userId,
    users.name AS firstName,
    users.surname AS lastName,
    users.photo AS photo,
    users.status AS
STATUS,
    UNIX_TIMESTAMP(users.birth_date) AS ubirthDate,
    users.sex AS sex,
    UNIX_TIMESTAMP(users.last_visit) AS ulastVisit,
    messages.text AS TEXT,
    DATE_FORMAT(messages.time, '%d.%m.%Y %H:%i') AS TIME,
    UNIX_TIMESTAMP(messages.time) AS utime,
    messages.unread AS unread,
    messages.attachment AS attachments
FROM
    messages
INNER JOIN users ON(messages.sender_id = users.id) 
WHERE
    (messages.id, users.id) IN(
    SELECT
        MAX(messages.id),
        users.id
    FROM
        messages
    JOIN users ON(messages.sender_id = users.id) OR(
            messages.reciever_id = users.id
        )
    WHERE
        (
            messages.reciever_id = 1 AND messages.sender_id = users.id
        ) OR(
            messages.reciever_id = users.id AND messages.sender_id = 1
        )
    GROUP BY
        users.id
    ORDER BY
        id
)
UNION
DISTINCT
  (SELECT 
    messages.id AS id,
    messages.sender_id AS senderId,
    messages.reciever_id AS receiverId,
    users.id AS userId,
    users.name AS firstName,
    users.surname AS lastName,
    users.photo AS photo,
    users.status AS
STATUS,
    UNIX_TIMESTAMP(users.birth_date) AS ubirthDate,
    users.sex AS users_sex,
    UNIX_TIMESTAMP(users.last_visit) AS ulastVisit,
    messages.text AS TEXT,
    DATE_FORMAT(messages.time, '%d.%m.%Y %H:%i') AS TIME,
    UNIX_TIMESTAMP(messages.time) AS utime,
    messages.unread AS unread,
    messages.attachment AS attachments
FROM
    messages
INNER JOIN users ON(messages.reciever_id = users.id)
WHERE
    (messages.id, users.id) IN(
    SELECT
        MAX(messages.id),
        users.id
    FROM
        messages
    JOIN users ON(messages.sender_id = users.id) OR(
            messages.reciever_id = users.id
        )
    WHERE
        (
            messages.reciever_id = 1 AND messages.sender_id = users.id
        ) OR(
            messages.reciever_id = users.id AND messages.sender_id = 1
        )
    GROUP BY
        users.id
    ORDER BY
        id
))) AS union1 ORDER BY 
  union1.utime DESC;
  */
        $response = $this->mysqli->query("SELECT messages.id AS id, messages.sender_id AS senderId, messages.reciever_id AS receiverId, users.name AS firstName, users.surname AS lastName, users.photo AS photo, users.status AS status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate,users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit,
        messages.text AS text, DATE_FORMAT(messages.time,'%d.%m.%Y %H:%i') AS time, UNIX_TIMESTAMP(messages.time) AS utime, messages.unread AS unread, messages.attachment AS attachments FROM messages
        INNER JOIN users ON (messages.sender_id=users.id) OR (messages.reciever_id=users.id)
        WHERE (messages.id, users.id) IN ( SELECT MAX(messages.id), users.id FROM messages
        JOIN users ON (messages.sender_id=users.id) OR (messages.reciever_id=users.id) WHERE (messages.reciever_id=$id AND messages.sender_id=users.id) OR (messages.reciever_id=users.id AND messages.sender_id=$id)
        GROUP BY users.id ORDER BY id ) ORDER BY utime DESC");

        $json     = [];
        while ($i = $response->fetch_assoc()) {
            $dialog                              = [];
            $dialog['secondUser']['id']          = $i['senderId'] != $id ? $i['senderId'] : $i['receiverId'];
            $dialog['secondUser']['firstName']   = $i['firstName'];
            $dialog['secondUser']['lastName']    = $i['lastName'];
            $dialog['secondUser']['photo']       = $i['photo'];
            $dialog['secondUser']['status']      = $i['status'];
            $dialog['secondUser']['birthDate']   = $i['birthDate'];
            $dialog['secondUser']['ubirthDate']  = $i['ubirthDate'];
            $dialog['secondUser']['sex']         = $i['sex'];
            $dialog['secondUser']['lastVisit']   = $i['lastVisit'];
            $dialog['secondUser']['ulastVisit']  = $i['ulastVisit'];
            $dialog['lastMessage']['id']         = $i['id'];
            $dialog['lastMessage']['senderId']   = $i['senderId'];
            $dialog['lastMessage']['receiverId'] = $i['receiverId'];
            $dialog['lastMessage']['text']       = $i['text'];
            $dialog['lastMessage']['time']       = $i['time'];
            $dialog['lastMessage']['utime']      = $i['utime'];
            $dialog['lastMessage']['unread']     = $i['unread'];
            $dialog['lastMessage']['attachments']     = $i['attachments'];
            if ($dialog['lastMessage']['unread'] == 1) {
                $responseUnread        = $this->mysqli->query("SELECT `id` FROM messages WHERE (`sender_id` = " . $dialog['secondUser']['id'] . " AND `reciever_id` ='$id' AND unread=1) OR (`sender_id` ='$id' AND `reciever_id` =" . $dialog['secondUser']['id'] . " AND unread=1)");
                $dialog['unreadCount'] = $responseUnread->num_rows;
            } else {
                $dialog['unreadCount'] = 0;
            }
            $json[] = $dialog;
        }
        return $json;
    }
    public function Dialogs2(int $id)
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('secondUser',JSON_OBJECT('id',users.id,'firstName', users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate',DATE_FORMAT(users.birth_date ,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit',DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i'),'ulastVisit',UNIX_TIMESTAMP(users.last_visit)),
        'lastMessage',JSON_OBJECT('id',messages.id,'senderId',messages.sender_id,'receiverId',messages.reciever_id,'text',messages.text,'attachments',messages.attachment,'time',messages.time,'utime',UNIX_TIMESTAMP(messages.time),'unread',messages.unread),'unreadCount',(SELECT count(*) from messages WHERE (messages.sender_id=users.id OR messages.reciever_id=users.id) AND messages.unread=1)))
        FROM messages
                    INNER JOIN users ON (messages.sender_id=users.id) OR (messages.reciever_id=users.id)
                    WHERE (messages.id, users.id) IN ( SELECT MAX(messages.id), users.id FROM messages
                    JOIN users ON (messages.sender_id=users.id) OR (messages.reciever_id=users.id) WHERE (messages.reciever_id='$id' AND messages.sender_id=users.id) OR (messages.reciever_id=users.id AND messages.sender_id='$id')
                    GROUP BY users.id ORDER BY id ) ORDER BY time DESC");

        $json = [];
        while ($i = $response->fetch_array()) {
            $json[] = json_decode($i[0], true);
        }
        // return json_decode($json, true);
        return $json;
    }
    public function Dialogs3(int $id)
    {
        $response = $this->mysqli->query("SELECT 
        (
          JSON_OBJECT(
            'secondUser', 
            JSON_OBJECT(
              'id', userId, 'firstName', firstName, 
              'lastName', lastName, 'photo', photo, 
              'status', status, 'ubirthDate', ubirthDate, 
              'sex', sex, 'ulastVisit', ulastVisit
            ), 
            'lastMessage', 
            JSON_OBJECT(
              'id', id, 'senderId', senderId, 'receiverId', 
              receiverId, 'text', text, 'attachments', 
              attachments, 'time', TIME, 'utime', 
              utime, 'unread', unread
            ), 
            'unreadCount', 
            unreadCount
          )
        ) 
      FROM 
        (
          (
            SELECT 
              messages.id AS id, 
              messages.sender_id AS senderId, 
              messages.reciever_id AS receiverId, 
              users.id AS userId, 
              users.name AS firstName, 
              users.surname AS lastName, 
              users.photo AS photo, 
              users.status AS STATUS, 
              UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, 
              users.sex AS sex, 
              UNIX_TIMESTAMP(users.last_visit) AS ulastVisit, 
              messages.text AS TEXT, 
              DATE_FORMAT(messages.time, '%d.%m.%Y %H:%i') AS TIME, 
              UNIX_TIMESTAMP(messages.time) AS utime, 
              messages.unread AS unread, 
              messages.attachment AS attachments, 
              (
                SELECT 
                  count(*) 
                from 
                  messages 
                WHERE 
                  messages.sender_id = users.id AND messages.reciever_id = '$id'
                  AND messages.unread = 1
              ) as unreadCount 
            FROM 
              messages 
              INNER JOIN users ON(messages.sender_id = users.id) 
              WHERE messages.id = greatest(COALESCE((SELECT max(id) from messages where sender_id = users.id and reciever_id = '$id'),0),COALESCE((SELECT max(id) from messages where reciever_id = users.id and sender_id = '$id'),0))
          )
          union distinct
          (
            SELECT 
              messages.id AS id, 
              messages.sender_id AS senderId, 
              messages.reciever_id AS receiverId, 
              users.id AS userId, 
              users.name AS firstName, 
              users.surname AS lastName, 
              users.photo AS photo, 
              users.status AS STATUS, 
              UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, 
              users.sex AS sex, 
              UNIX_TIMESTAMP(users.last_visit) AS ulastVisit, 
              messages.text AS TEXT, 
              DATE_FORMAT(messages.time, '%d.%m.%Y %H:%i') AS TIME, 
              UNIX_TIMESTAMP(messages.time) AS utime, 
              messages.unread AS unread, 
              messages.attachment AS attachments, 
              (
                SELECT 
                  count(*) 
                from 
                  messages 
                WHERE 
                messages.sender_id = '$id' AND messages.reciever_id = users.id
                  AND messages.unread = 1
              ) as unreadCount 
            FROM 
              messages 
              INNER JOIN users ON(messages.reciever_id = users.id) 
              WHERE messages.id = greatest(COALESCE((SELECT max(id) from messages where sender_id = users.id and reciever_id = '$id'),0),COALESCE((SELECT max(id) from messages where reciever_id = users.id and sender_id = '$id'),0))
          )
        ) AS union1 
      ORDER BY 
        union1.utime DESC;
      ");

        $json = [];
        while ($i = $response->fetch_array()) {
            $json[] = json_decode($i[0], true);
        }
        // return json_decode($json, true);
        return $json;
    }
    public function Dialogs4(int $id)
    {
        $response = $this->mysqli->query("(SELECT (JSON_OBJECT('secondUser',JSON_OBJECT('id',users.id,'firstName', users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate',DATE_FORMAT(users.birth_date ,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit',DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i'),'ulastVisit',UNIX_TIMESTAMP(users.last_visit)),
        'lastMessage',JSON_OBJECT('id',messages.id,'senderId',messages.sender_id,'receiverId',messages.reciever_id,'text',messages.text,'attachments',messages.attachment,'time',messages.time,'utime',UNIX_TIMESTAMP(messages.time),'unread',messages.unread),'unreadCount',(SELECT count(*) from messages WHERE (messages.sender_id=users.id) AND messages.unread=1)))
        FROM messages
                    INNER JOIN users ON (messages.sender_id=users.id)
                    WHERE (messages.id, users.id) IN ( SELECT MAX(messages.id), users.id FROM messages
                    JOIN users ON (messages.sender_id=users.id) WHERE (messages.reciever_id='1')
                    GROUP BY users.id  ) )
                     UNION
                    (SELECT (JSON_OBJECT('secondUser',JSON_OBJECT('id',users.id,'firstName', users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate',DATE_FORMAT(users.birth_date ,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit',DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i'),'ulastVisit',UNIX_TIMESTAMP(users.last_visit)),
        'lastMessage',JSON_OBJECT('id',messages.id,'senderId',messages.sender_id,'receiverId',messages.reciever_id,'text',messages.text,'attachments',messages.attachment,'time',messages.time,'utime',UNIX_TIMESTAMP(messages.time),'unread',messages.unread),'unreadCount',(SELECT count(*) from messages WHERE (messages.reciever_id=users.id) AND messages.unread=1)))
        FROM messages
                    INNER JOIN users ON (messages.reciever_id=users.id)
                    WHERE (messages.id, users.id) IN ( SELECT MAX(messages.id), users.id FROM messages
                    JOIN users ON (messages.reciever_id=users.id) WHERE (messages.sender_id='1')
                    GROUP BY users.id ) )
                    ");

        $json = [];
        while ($i = $response->fetch_array()) $json[] = json_decode($i[0], true);
        // return json_decode($json, true);
        return  $json;
    }
    public function DialogSpecificUser(int $firstId, int $secondId)
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('secondUser',JSON_OBJECT('id',users.id,'firstName', users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate',DATE_FORMAT(users.birth_date ,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit',DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i'),'ulastVisit',UNIX_TIMESTAMP(users.last_visit)),
        'lastMessage',JSON_OBJECT('id',messages.id,'senderId',messages.sender_id,'receiverId',messages.reciever_id,'text',messages.text,'time',messages.time,'utime',UNIX_TIMESTAMP(messages.time),'unread',messages.unread),'unreadCount',(SELECT count(*) from messages WHERE (messages.sender_id=users.id OR messages.reciever_id=users.id) AND messages.unread=1)))
        FROM messages
                    INNER JOIN users ON (messages.sender_id='$secondId') OR (messages.reciever_id='$secondId')
                    WHERE (messages.id, users.id) IN ( SELECT MAX(messages.id), users.id FROM messages
                    JOIN users ON (messages.sender_id=users.id) OR (messages.reciever_id=users.id) WHERE (messages.reciever_id='$firstId' AND messages.sender_id=users.id) OR (messages.reciever_id=users.id AND messages.sender_id='$firstId')
                    GROUP BY users.id ORDER BY id ) ORDER BY time DESC");

        $json = [];
        while ($i = $response->fetch_array()) {
            $json[] = json_decode($i[0], true);
        }
        // return json_decode($json, true);
        return $json;
    }
    public function DialogSpecificUser2(int $firstId, int $secondId)
    {
        $response = $this->mysqli->query("SELECT 
        (
          JSON_OBJECT(
            'secondUser', 
            JSON_OBJECT(
              'id', userId, 'firstName', firstName, 
              'lastName', lastName, 'photo', photo, 
              'status', status, 'ubirthDate', ubirthDate, 
              'sex', sex, 'ulastVisit', ulastVisit
            ), 
            'lastMessage', 
            JSON_OBJECT(
              'id', id, 'senderId', senderId, 'receiverId', 
              receiverId, 'text', text, 'attachments', 
              attachments, 'time', TIME, 'utime', 
              utime, 'unread', unread
            ), 
            'unreadCount', 
            unreadCount
          )
        ) 
      FROM 
        (
          (
            SELECT 
              messages.id AS id, 
              messages.sender_id AS senderId, 
              messages.reciever_id AS receiverId, 
              users.id AS userId, 
              users.name AS firstName, 
              users.surname AS lastName, 
              users.photo AS photo, 
              users.status AS STATUS, 
              UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, 
              users.sex AS sex, 
              UNIX_TIMESTAMP(users.last_visit) AS ulastVisit, 
              messages.text AS TEXT, 
              DATE_FORMAT(messages.time, '%d.%m.%Y %H:%i') AS TIME, 
              UNIX_TIMESTAMP(messages.time) AS utime, 
              messages.unread AS unread, 
              messages.attachment AS attachments, 
              (
                SELECT 
                  count(*) 
                from 
                  messages 
                WHERE 
                  messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId'
                  AND messages.unread = 1
              ) as unreadCount 
            FROM 
              messages 
              INNER JOIN users ON(messages.sender_id = '$secondId') 
              WHERE messages.id = greatest(COALESCE((SELECT max(id) from messages where sender_id = '$secondId' and reciever_id = '$firstId'),0),COALESCE((SELECT max(id) from messages where reciever_id = '$secondId' and sender_id = '$firstId'),0))
          )
          union distinct
          (
            SELECT 
              messages.id AS id, 
              messages.sender_id AS senderId, 
              messages.reciever_id AS receiverId, 
              users.id AS userId, 
              users.name AS firstName, 
              users.surname AS lastName, 
              users.photo AS photo, 
              users.status AS STATUS, 
              UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, 
              users.sex AS sex, 
              UNIX_TIMESTAMP(users.last_visit) AS ulastVisit, 
              messages.text AS TEXT, 
              DATE_FORMAT(messages.time, '%d.%m.%Y %H:%i') AS TIME, 
              UNIX_TIMESTAMP(messages.time) AS utime, 
              messages.unread AS unread, 
              messages.attachment AS attachments, 
              (
                SELECT 
                  count(*) 
                from 
                  messages 
                WHERE 
                messages.sender_id = '$firstId' AND messages.reciever_id = '$secondId'
                  AND messages.unread = 1
              ) as unreadCount 
            FROM 
              messages 
              INNER JOIN users ON(messages.reciever_id = '$secondId') 
              WHERE messages.id = greatest(COALESCE((SELECT max(id) from messages where sender_id = '$secondId' and reciever_id = '$firstId'),0),COALESCE((SELECT max(id) from messages where reciever_id = '$secondId' and sender_id = '$firstId'),0))
          )
        ) AS union1 
      ORDER BY 
        union1.utime DESC;
      ");

        $json = [];
        while ($i = $response->fetch_array()) {
            $json[] = json_decode($i[0], true);
        }
        // return json_decode($json, true);
        return $json;
    }
    /*Пагинация всех сообщений
    public function GetAllMessages(int $firstId, int $start = null, int $end = null, bool $desc = false)
    {
        $desc = ($desc ? 'DESC' : '');
        $limit = ($start ? "LIMIT $start, $end" : '');

        $response = $this->mysqli->query("SELECT (JSON_OBJECT('messages',JSON_ARRAYAGG(JSON_OBJECT('id',msgs.id,'senderId',msgs.sender_id,'receiverId',msgs.reciever_id,'text',msgs.text,'time', DATE_FORMAT(msgs.time,'%H:%i:%s'),'date',DATE_FORMAT(msgs.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(time),'unread',msgs.unread,'attachment',msgs.attachment)),'secondUser',JSON_OBJECT('id',u2.id,'firstName',u2.name,'lastName',u2.surname,'photo',u2.photo,'status',u2.status,'birthDate', DATE_FORMAT(`u2`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u2.birth_date),'sex',u2.sex,'lastVisit', DATE_FORMAT(u2.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u2.last_visit))))
        FROM (SELECT * FROM messages
        WHERE messages.sender_id = '$firstId' OR messages.reciever_id = '$firstId'
        ORDER BY messages.id $desc $limit) as msgs
        INNER JOIN users AS u1 ON ('$firstId'=u1.id)
        INNER JOIN users AS u2 ON ('$firstId'<>u2.id AND (msgs.reciever_id=u2.id OR msgs.sender_id=u2.id))
        GROUP BY u2.id
        UNION
        SELECT (JSON_OBJECT('firstUser',JSON_OBJECT('id',users.id,'firstName',users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate', DATE_FORMAT(`users`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit', DATE_FORMAT(users.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(users.last_visit))))
        FROM users
        WHERE users.id = '$firstId'");
        $json = [];
        while ($i = $response->fetch_array()) {
            if ($desc && !$start && !$end) {
                $arr = json_decode($i['0'], true);
                if(isset($arr['messages'])) $arr['messages'] = array_reverse($arr['messages']);
                $json[] = $arr;
            } else {
                $json[] = json_decode($i['0'], true);
            }
        }
        return $json;
    }
    *//*
    public function GetAllMessages(int $firstId)
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('messages',JSON_ARRAYAGG(JSON_OBJECT('id',msgs.id,'senderId',msgs.sender_id,'receiverId',msgs.reciever_id,'text',msgs.text,'time', DATE_FORMAT(msgs.time,'%H:%i:%s'),'date',DATE_FORMAT(msgs.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(time),'unread',msgs.unread,'attachment',msgs.attachment)),'secondUser',JSON_OBJECT('id',u2.id,'firstName',u2.name,'lastName',u2.surname,'photo',u2.photo,'status',u2.status,'birthDate', DATE_FORMAT(`u2`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u2.birth_date),'sex',u2.sex,'lastVisit', DATE_FORMAT(u2.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u2.last_visit))))
        FROM (SELECT * FROM messages
        WHERE messages.sender_id = '$firstId' OR messages.reciever_id = '$firstId'
        ORDER BY messages.id) as msgs
        INNER JOIN users AS u1 ON ('$firstId'=u1.id)
        INNER JOIN users AS u2 ON ('$firstId'<>u2.id AND (msgs.reciever_id=u2.id OR msgs.sender_id=u2.id))
        GROUP BY u2.id
        UNION
        SELECT (JSON_OBJECT('firstUser',JSON_OBJECT('id',users.id,'firstName',users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate', DATE_FORMAT(`users`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit', DATE_FORMAT(users.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(users.last_visit))))
        FROM users
        WHERE users.id = '$firstId'");
        $json = [];
        while ($i = $response->fetch_array()) {
            $json[] = json_decode($i['0'], true);
        }
        return $json;
    }*/
    public function GetAllMessages(int $firstId, bool $unread = false, int $start = null, int $end = null)
    {
        //$desc = ($desc ? 'DESC' : ''); не пашет с джсоном
        $limit = ($start ? "AND (messages.id > '$start')" : '');
        $start = ($start ? $start : '0');
        $limit = ($end ? "AND (messages.id > $start AND messages.id < $end)" : $limit);
        $where = ($unread ? "(messages.reciever_id = '$firstId' AND messages.unread = '1')" : "(messages.sender_id = '$firstId' OR messages.reciever_id = '$firstId')");

        $response = $this->mysqli->query("SELECT (JSON_OBJECT('messages',JSON_ARRAYAGG(JSON_OBJECT('id',msgs.id,'senderId',msgs.sender_id,'receiverId',msgs.reciever_id,'text',msgs.text,'time', DATE_FORMAT(msgs.time,'%H:%i:%s'),'date',DATE_FORMAT(msgs.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(time),'unread',msgs.unread,'attachments',msgs.attachment)),'secondUser',JSON_OBJECT('id',u2.id,'firstName',u2.name,'lastName',u2.surname,'photo',u2.photo,'status',u2.status,'birthDate', DATE_FORMAT(`u2`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u2.birth_date),'sex',u2.sex,'lastVisit', DATE_FORMAT(u2.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u2.last_visit))))
        FROM (SELECT * FROM messages
        WHERE $where $limit
        ORDER BY messages.id) as msgs
        INNER JOIN users AS u1 ON ('$firstId'=u1.id)
        INNER JOIN users AS u2 ON ('$firstId'<>u2.id AND (msgs.reciever_id=u2.id OR msgs.sender_id=u2.id))
        GROUP BY u2.id
        #UNION
        #SELECT (JSON_OBJECT('firstUser',JSON_OBJECT('id',users.id,'firstName',users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate', DATE_FORMAT(`users`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit', DATE_FORMAT(users.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(users.last_visit))))
        #FROM users
        #WHERE users.id = '$firstId'");
        $json = [];
        while ($i = $response->fetch_array()) {
            $temp = json_decode($i[0], true);
            $json[] = $temp;
        }
        return $json;
    }
    public function GetAllMessagesFromDialog(int $firstId, int $secondId, bool $unread = false, int $start = null, int $end = null, bool $sum = true, bool $descending  = false)
    {
        $desc = ($descending  ? 'DESC' : '');
        if ($sum === false) {
            $limit = ($start ? "AND (messages.id > '$start')" : '');
            $start = ($start ? $start : '0');
            $limit = ($end ? "AND (messages.id > $start AND messages.id < $end)" : $limit);
        } else {
            //if ($start) 
            $limit = "LIMIT $start, $end";
            //else $limit = '';
        }
        $where = ($unread ? "((messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId') AND messages.unread = '1')" : "((messages.sender_id = '$firstId' AND messages.reciever_id = '$secondId') OR (messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId'))");

        $response = $this->mysqli->query("SELECT (JSON_OBJECT('firstUser',JSON_OBJECT('id',u1.id,'firstName',u1.name,'lastName',u1.surname,'photo',u1.photo,'status',u1.status,'birthDate', DATE_FORMAT(`u1`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u1.birth_date),'sex',u1.sex,'lastVisit', DATE_FORMAT(u1.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u1.last_visit),'messageCount',(SELECT COUNT(`id`) AS `messageCount` FROM `messages` WHERE $where)),'secondUser',JSON_OBJECT('id',u2.id,'firstName',u2.name,'lastName',u2.surname,'photo',u2.photo,'status',u2.status,'birthDate', DATE_FORMAT(`u2`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u2.birth_date),'sex',u2.sex,'lastVisit', DATE_FORMAT(u2.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u2.last_visit),'messageCount',(SELECT COUNT(`id`) FROM `messages` WHERE `sender_id`='$secondId')),'messages',JSON_ARRAYAGG(JSON_OBJECT('id',msgs.id,'senderId',msgs.sender_id,'receiverId',msgs.reciever_id,'text',msgs.text,'time', DATE_FORMAT(msgs.time,'%H:%i:%s'),'date',DATE_FORMAT(msgs.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(time),'unread',msgs.unread,'attachments',msgs.attachment))))
        FROM (SELECT * FROM messages
        WHERE $where " . (!$sum ? $limit : '') . "
        ORDER BY messages.id $desc " . ($sum ? $limit : '') . ") as msgs
        INNER JOIN users AS u1 ON ('$firstId'=u1.id)
        INNER JOIN users AS u2 ON ('$secondId'=u2.id)");

        //".(!$sum ? $limit : '')."
        $json = $response->fetch_row();
        $json = json_decode($json[0], true);

        if (!is_array($json['firstUser'])) {
            $json['firstUser'] = json_decode($json['firstUser'], true);
            $json['secondUser'] = json_decode($json['secondUser'], true);
        }

        if ($descending) $json['messages'] = array_reverse($json['messages']);
        //$this->mysqli->query("UPDATE `messages` SET `unread` = '0' WHERE `messages`.`sender_id` = '$secondId' AND `messages`.`reciever_id` = '$firstId'");
        return $json;
    }

    public function GetNewMessages(int $firstId, int $secondId, int $start = 0)
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('firstUser',JSON_OBJECT('id',u1.id,'firstName',u1.name,'lastName',u1.surname,'photo',u1.photo,'status',u1.status,'birthDate', DATE_FORMAT(`u1`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u1.birth_date),'sex',u1.sex,'lastVisit', DATE_FORMAT(u1.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u1.last_visit)),'secondUser',JSON_OBJECT('id',u2.id,'firstName',u2.name,'lastName',u2.surname,'photo',u2.photo,'status',u2.status,'birthDate', DATE_FORMAT(`u2`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u2.birth_date),'sex',u2.sex,'lastVisit', DATE_FORMAT(u2.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u2.last_visit)),'messages',JSON_ARRAYAGG(JSON_OBJECT('id',messages.id,'senderId',messages.sender_id,'receiverId',messages.reciever_id,'text',messages.text,'time', DATE_FORMAT(messages.time,'%H:%i:%s'),'date',DATE_FORMAT(messages.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(time),'unread',messages.unread,'attachments',messages.attachment))))
        FROM messages
        INNER JOIN users AS u1 ON ('$firstId'=u1.id)
        INNER JOIN users AS u2 ON ('$secondId'=u2.id)
        WHERE " . ($start !== 0 ? "(messages.id > $start AND messages.unread = '1')" : "messages.unread = '1'") . "  AND (messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId')");

        $json = $response->fetch_row();

        //$this->mysqli->query("UPDATE `messages` SET `unread` = '0' WHERE `messages`.`sender_id` = '$secondId' AND `messages`.`reciever_id` = '$firstId'");
        $json = json_decode($json[0], true);
        if (!is_array($json['firstUser'])) {
            $json['firstUser'] = json_decode($json['firstUser'], true);
            $json['secondUser'] = json_decode($json['secondUser'], true);
        }
        return $json;
    }
    public function GetNewMessages2(int $firstId, int $secondId, int $start = 0)
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('messages',JSON_ARRAYAGG(JSON_OBJECT('id',messages.id,'senderId',messages.sender_id,'receiverId',messages.reciever_id,'text',messages.text,'time', DATE_FORMAT(messages.time,'%H:%i:%s'),'date',DATE_FORMAT(messages.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(time),'unread',messages.unread,'attachments',messages.attachment))))
        FROM messages
        INNER JOIN users AS u1 ON ('$firstId'=u1.id)
        INNER JOIN users AS u2 ON ('$secondId'=u2.id)
        WHERE " . ($start !== 0 ? "(messages.id > $start AND messages.unread = '1')" : "messages.unread = '1'") . "  AND (messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId')");

        $json = $response->fetch_row();

        //$this->mysqli->query("UPDATE `messages` SET `unread` = '0' WHERE `messages`.`sender_id` = '$secondId' AND `messages`.`reciever_id` = '$firstId'");
        $json = json_decode($json[0], true);
        return $json;
    }
    public function GetUnreadMessages(int $firstId, int $lastMsg = null)
    {
        $lastMsg = ($lastMsg ? "AND (messages.id > '$lastMsg')" : '');
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('messages',JSON_ARRAYAGG(JSON_OBJECT('id',messages.id,'senderId',messages.sender_id,'receiverId',messages.reciever_id,'text',messages.text,'time', DATE_FORMAT(messages.time,'%H:%i:%s'),'date',DATE_FORMAT(messages.time,'%d.%m.%Y'),'utime',UNIX_TIMESTAMP(time),'unread',messages.unread,'attachments',messages.attachment)),'secondUser',JSON_OBJECT('id',u2.id,'firstName',u2.name,'lastName',u2.surname,'photo',u2.photo,'status',u2.status,'birthDate', DATE_FORMAT(`u2`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(u2.birth_date),'sex',u2.sex,'lastVisit', DATE_FORMAT(u2.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(u2.last_visit))))
        FROM messages
        INNER JOIN users AS u1 ON ('$firstId'=u1.id)
        INNER JOIN users AS u2 ON ('$firstId'<>u2.id AND messages.sender_id=u2.id)
        WHERE (messages.unread = '1' AND messages.reciever_id = '$firstId') $lastMsg GROUP BY u2.id");
        /*
        UNION
        SELECT (JSON_OBJECT('firstUser',JSON_OBJECT('id',users.id,'firstName',users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate', DATE_FORMAT(`users`.`birth_date`,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit', DATE_FORMAT(users.`last_visit`,'%d.%m.%Y %H:%i'), 'ulastVisit',UNIX_TIMESTAMP(users.last_visit))))
        FROM users
        WHERE users.id = '$firstId'
        */
        $json = [];
        while ($i = $response->fetch_array()) {
            $temp = json_decode($i[0], true);
            if (isset($temp['secondUser'])) {
                //$temp['secondUser'] = json_decode($temp['secondUser'], true);
            }
            $json[] = $temp;
        }
        if (isset($json[0]['messages'])) return $json;
        return false;
    }
    public function UnreadMessages(int $firstId, int $secondId)
    {
        try {
            $stmt = $this->mysqli->prepare("UPDATE `messages` SET `unread` = '0' WHERE `messages`.`sender_id` = ? AND `messages`.`reciever_id` = ?");
            $stmt->bind_param('ii', $secondId, $firstId);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    public function SendMessage(int $firstId, int $secondId, string $text)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
        if (mb_strlen($text) !== 0) {
            if (mb_strlen($text) > 5000) $text = mb_strimwidth($text, 0, 5000, '...');
            $tempParse = fcknParse($text, @$this->headers['parse-mode'] ?? 'text');
            $text = $tempParse[0];
            $result = $tempParse[1] ?? null;
            $stmt = $this->mysqli->prepare('INSERT INTO `messages` (`id`,`sender_id`,`reciever_id`,`text`,`attachment`) VALUES (NULL,?,?,?,?)');

            if (empty($result)) $result = [];
            $resultPrepared = json_encode($result, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $stmt->bind_param('iiss', $firstId, $secondId, $text, $resultPrepared);
            try {
                if ($stmt->execute()) {
                    $json       = [];
                    $json['id'] = $this->mysqli->insert_id;
                    $json['senderId'] = $firstId;
                    $json['utime'] = time();
                    $json['attachments'] = $result ?? [];
                    $json['unread'] = true;
                    $json['text'] = $text;
                    return $json;
                }
            } catch (mysqli_sql_exception $e) {
                print_r($e);
                return false;
            }
        }

        return false;
    }
    public function PostGet(int $postId = null, int $info = 0)
    {
        if ($postId == null) {
            $response = $this->mysqli->query("SELECT posts.id, posts.user_id AS 'userId', posts.title, posts.text, attachment AS 'attachments', DATE_FORMAT(posts.time,'%d.%m.%Y') AS date, DATE_FORMAT(posts.time,'%H:%i') AS time, UNIX_TIMESTAMP(posts.time) AS utime, posts.edited, posts.last_edit AS 'lastModified' FROM posts ORDER BY id DESC");
        } else {
            $response = $this->mysqli->query("SELECT posts.id, posts.user_id AS 'userId', posts.title, posts.text, attachment AS 'attachments', DATE_FORMAT(posts.time,'%d.%m.%Y') AS date, DATE_FORMAT(posts.time,'%H:%i') AS time, UNIX_TIMESTAMP(posts.time) AS utime, posts.edited, posts.last_edit AS 'lastModified' FROM posts WHERE posts.id =$postId ORDER BY id DESC");
        }
        $json = [];
        if ($response->num_rows === 0) throw new Exception('Post is not found!', 404);
        while ($post = $response->fetch_assoc()) {
            $post['attachments'] = json_decode($post['attachments']);
            if ($info !== 0) {
                $responseAuthor = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE id=" . $post['userId']);
                $post['author'] = [];
                while ($author = $responseAuthor->fetch_assoc()) {
                    $post['author'] = $author;
                }
            }
            $responseLikes      = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM likedposts INNER JOIN users ON likedposts.user_id = users.id WHERE `post_id` = " . $post['id']);
            $post['likedUsers'] = [];
            while ($like = $responseLikes->fetch_assoc()) {
                $post['likedUsers'][] =  $like;
            }
            $post['likesCount'] = count($post['likedUsers']);
            $responseComments   = $this->mysqli->query("SELECT comments.id, comments.post_id AS 'postId', comments.user_id AS 'userId', comments.text, DATE_FORMAT(comments.time,'%d.%m.%Y') AS date, DATE_FORMAT(comments.time,'%H:%i') AS time, UNIX_TIMESTAMP(comments.time) AS utime, comments.edited as 'edited', comments.last_edit AS 'lastModified', comments.attachment as 'attachments' FROM comments  WHERE `post_id` = " . $post['id']);
            $post['comments']   = [];
            while ($comment = $responseComments->fetch_assoc()) {
                $comment['attachments'] = json_decode($comment['attachments']);
                $responseCommentsAuthor = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE id=" . $comment['userId']);
                $comment['author']      = [];
                while ($author = $responseCommentsAuthor->fetch_assoc()) {
                    $comment['author'] = $author;
                }
                $post['comments'][] =  $comment;
            }
            $post['commentsCount'] = count($post['comments']);
            if ($postId != null) {
                $json = $post;
            } else {
                $json[] = $post;
            }
        }
        return $json;
    }
    public function PostGetSpecific(int $id = null, int $info = 0)
    {
        if ($id == null) {
            return false;
        } else {
            $response = $this->mysqli->query("SELECT posts.id, posts.user_id AS 'userId', posts.title, posts.text, posts.attachment as 'attachments', DATE_FORMAT(posts.time,'%d.%m.%Y') AS date, DATE_FORMAT(posts.time,'%H:%i') AS time, UNIX_TIMESTAMP(posts.time) AS utime, posts.edited, posts.last_edit AS 'lastModified' FROM posts WHERE posts.user_id =" . (int) $id . " ORDER BY id DESC");
        }
        $json = [];
        if ($response->num_rows === 0) {
            throw new Exception('Post is not found!', 404);
        }
        while ($post = $response->fetch_assoc()) {
            if ($info != 0) {
                $responseAuthor = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE id=" . $post['userId']);
                $post['author'] = [];
                while ($author = $responseAuthor->fetch_assoc()) {
                    $post['author'] = $author;
                }
            }
            $post['attachments'] = json_decode($post['attachments']);
            $responseLikes      = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM likedposts INNER JOIN users ON likedposts.user_id = users.id WHERE `post_id` = " . $post['id']);
            $post['likedUsers'] = [];
            while ($like = $responseLikes->fetch_assoc()) {
                array_push($post['likedUsers'], $like);
            }
            $post['likesCount'] = count($post['likedUsers']);
            $responseComments   = $this->mysqli->query("SELECT comments.id, comments.post_id AS 'postId', comments.user_id AS 'userId', comments.text, DATE_FORMAT(comments.time,'%d.%m.%Y') AS date, DATE_FORMAT(comments.time,'%H:%i') AS time, UNIX_TIMESTAMP(comments.time) AS utime FROM comments  WHERE `post_id` = " . $post['id']);
            $post['comments']   = [];
            while ($comment = $responseComments->fetch_assoc()) {
                $responseCommentsAuthor = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE id=" . $comment['userId']);
                $comment['author']      = [];
                while ($author = $responseCommentsAuthor->fetch_assoc()) {
                    $comment['author'] = $author;
                }
                array_push($post['comments'], $comment);
            }
            $post['commentsCount'] = count($post['comments']);
            $json[] = $post;
        }
        return $json;
    }
    public function PostPagination(int $id = 0, int $start = 0, int $limit = 0, int $info = 0, int $type = null)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
        if ($id != 0) {
            if ($type == null) {
                $response = $this->mysqli->query("SELECT posts.id, posts.user_id AS 'userId', posts.title, posts.text, posts.attachment as 'attachments', DATE_FORMAT(posts.time,'%d.%m.%Y') AS date, DATE_FORMAT(posts.time,'%H:%i') AS time, UNIX_TIMESTAMP(posts.time) AS utime, posts.edited, posts.last_edit AS 'lastModified' FROM posts WHERE `user_id` = " . (int) $id . " ORDER BY id DESC LIMIT " . (int) $start . ", " . (int) $limit . "");
            } else {
                $response = $this->mysqli->query("SELECT posts.id, posts.user_id AS 'userId', posts.title, posts.text, posts.attachment as 'attachments', DATE_FORMAT(posts.time,'%d.%m.%Y') AS date, DATE_FORMAT(posts.time,'%H:%i') AS time, UNIX_TIMESTAMP(posts.time) AS utime, posts.edited,posts.last_edit AS 'lastModified' FROM posts WHERE `user_id` = " . (int) $id . " AND (`id` < " . (int) $start . " AND `id` >= " . (int) $limit . ") ORDER BY id DESC");
            }
        } else {
            $response = $this->mysqli->query("SELECT posts.id, posts.user_id AS 'userId', posts.title, posts.text, posts.attachment as 'attachments', DATE_FORMAT(posts.time,'%d.%m.%Y') AS date, DATE_FORMAT(posts.time,'%H:%i') AS time, UNIX_TIMESTAMP(posts.time) AS utime, posts.edited, posts.last_edit AS 'lastModified' FROM posts ORDER BY id DESC LIMIT " . (int) $start . ", " . (int) $limit . "");
        }
        $json = [];
        while ($post = $response->fetch_assoc()) {
            $post['attachments'] = json_decode($post['attachments']);
            if ($info != 0) {
                $responseAuthor = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE id=" . $post['userId']);
                $post['author'] = [];
                while ($author = $responseAuthor->fetch_assoc()) {
                    $post['author']           = $author;
                    $post['author']['online'] = online($post['author']['lastVisit']);
                }
            }
            $responseLikes      = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM likedposts INNER JOIN users ON likedposts.user_id = users.id WHERE `post_id` = " . $post['id']);
            $post['likedUsers'] = [];
            while ($like = $responseLikes->fetch_assoc()) {
                array_push($post['likedUsers'], $like);
            }
            $post['likesCount'] = count($post['likedUsers']);
            $responseComments   = $this->mysqli->query("SELECT comments.id, comments.post_id AS 'postId', comments.user_id AS 'userId', comments.text, DATE_FORMAT(comments.time,'%d.%m.%Y') AS date, DATE_FORMAT(comments.time,'%H:%i') AS time, UNIX_TIMESTAMP(comments.time) AS utime, comments.edited as 'edited', comments.last_edit AS 'lastModified', comments.attachment as 'attachments' FROM comments  WHERE `post_id` = " . $post['id']);
            $post['comments']   = [];
            while ($comment = $responseComments->fetch_assoc()) {
                $comment['attachments'] = json_decode($comment['attachments']);
                $responseCommentsAuthor = $this->mysqli->query("SELECT users.id, users.name AS firstName, users.surname AS lastName, users.photo, users.status, DATE_FORMAT(users.birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, users.sex, DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(users.last_visit) AS ulastVisit FROM users WHERE id=" . $comment['userId']);
                $comment['author']      = [];
                while ($author = $responseCommentsAuthor->fetch_assoc()) {
                    $comment['author']           = $author;
                    $comment['author']['online'] = online($comment['author']['lastVisit']);
                }
                array_push($post['comments'], $comment);
            }
            $post['commentsCount'] = count($post['comments']);
            $json[] = $post;
        }
        return $json;
    }
    public function PostAdd(int $id, string $title = '', string $text)
    {
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';

            $tempParse = fcknParse($text, @$this->headers['parse-mode'] ?? 'text');
            $text = $tempParse[0];
            $result = $tempParse[1] ?? null;

            if (mb_strlen($title) > 50) $title = mb_strimwidth($title, 0, 50, '...');
            if (mb_strlen($text) > 5000) $text = mb_strimwidth($text, 0, 5000, '...');

            if (empty($result)) $result = [];
            $resultPrepared = json_encode($result, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $stmt = $this->mysqli->prepare('INSERT INTO `posts` (`user_id`,`title`,`text`,`attachment`) VALUES(?,?,?,?)');
            $stmt->bind_param('isss', $id, $title, $text, $resultPrepared);
            if ($stmt->execute()) return $this->mysqli->insert_id;
            else return false;
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    public function PostEdit(int $id, string $title, string $text, int $postId)
    {
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';

            $tempParse = fcknParse($text, @$this->headers['parse-mode'] ?? 'text');
            $text = $tempParse[0];
            $result = $tempParse[1] ?? null;

            if (mb_strlen($title) > 50) $title = mb_strimwidth($title, 0, 50, '...');
            if (mb_strlen($text) > 5000) $text = mb_strimwidth($text, 0, 5000, '...');

            if (empty($result)) $result = [];
            $resultPrepared = json_encode($result, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $stmt = $this->mysqli->prepare("UPDATE `posts` SET `title` = ?, `text` = ?, `edited` = '1', `last_edit` = CURRENT_TIMESTAMP(), `attachment` = ? WHERE `posts`.`id` = ? AND `posts`.`user_id`= ?");
            $stmt->bind_param('sssii', $title, $text, $resultPrepared, $postId, $id);
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    public function PostDel(int $id, int $postId)
    {
        try {
            $stmt = $this->mysqli->prepare('DELETE FROM `posts` WHERE `posts`.`id` = ? AND `posts`.`user_id` = ?');
            $stmt->bind_param('ii', $postId, $id);
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }

    public function CommentGet2(int $commentId)
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('id',comments.id, 'postId', comments.post_id, 'userId',comments.user_id, 'text', comments.text, 'date', DATE_FORMAT(comments.time,'%d.%m.%Y'), 'time',DATE_FORMAT(comments.time,'%H:%i'), 'utime',UNIX_TIMESTAMP(comments.time),'edited',comments.edited,'lastModified',comments.last_edit,'attachments',comments.attachment,'author', JSON_OBJECT('id',users.id,'firstName', users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate',DATE_FORMAT(users.birth_date ,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit',DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i'),'ulastVisit',UNIX_TIMESTAMP(users.last_visit))))
        FROM comments INNER JOIN users ON comments.user_id = users.id WHERE comments.id = '$commentId' ORDER BY comments.id DESC");
        $json = $response->fetch_row();
        if ($response->num_rows === 0) throw new Exception('Comment is not found!', 404);

        $json = json_decode($json[0], true);
        return $json;
    }
    public function CommentsGet()
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('id',comments.id, 'postId', comments.post_id, 'userId',comments.user_id, 'text', comments.text, 'date', DATE_FORMAT(comments.time,'%d.%m.%Y'), 'time',DATE_FORMAT(comments.time,'%H:%i'), 'utime',UNIX_TIMESTAMP(comments.time),'author', JSON_OBJECT('id',users.id,'firstName', users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate',DATE_FORMAT(users.birth_date ,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit',DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i'),'ulastVisit',UNIX_TIMESTAMP(users.last_visit))))
        FROM comments INNER JOIN users ON comments.user_id = users.id ORDER BY comments.id DESC");
        $json = [];
        while ($i = $response->fetch_array()) {
            $json[] = json_decode($i[0], true);
        }
        return $json;
    }

    public function CommentAdd(int $id, int $postId, string $text)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
        $tempParse = fcknParse($text, @$this->headers['parse-mode'] ?? 'text');
        $text = $tempParse[0];
        $result = $tempParse[1] ?? null;
        if (mb_strlen($text) > 2000) $text = mb_strimwidth($text, 0, 2000, '...');

        if (empty($result)) $result = [];
        $resultPrepared = json_encode($result, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt = $this->mysqli->prepare('INSERT INTO `comments` (`id`, `post_id`, `user_id`, `text`, `attachment`) VALUES (NULL,?,?,?,?)');
        $stmt->bind_param('iiss', $postId, $id, $text, $resultPrepared);
        if ($stmt->execute()) return $this->mysqli->insert_id;
        else return false;
    }
    public function CommentEdit(int $id, int $commentId, string $text)
    {
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
            $tempParse = fcknParse($text, @$this->headers['parse-mode'] ?? 'text');
            $text = $tempParse[0];
            $result = $tempParse[1] ?? null;

            if (empty($result)) $result = [];
            $resultPrepared = json_encode($result, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $stmt = $this->mysqli->prepare("UPDATE `comments` SET `text` = ?, `edited` = '1', `last_edit` = CURRENT_TIMESTAMP(), `attachment` = ? WHERE `comments`.`id` = ? AND `comments`.`user_id`= ?");
            $stmt->bind_param('ssii', $text, $resultPrepared, $commentId, $id);
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }

    public function CommentDel2(int $id, int $commentId)
    {
        try {
            $stmt = $this->mysqli->prepare('DELETE FROM `comments` WHERE `comments`.`id` = ? AND `comments`.`user_id` = ?');
            $stmt->bind_param('ii', $commentId, $id);
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    public function LikeGet(int $postId)
    {
        $response = $this->mysqli->query("SELECT (JSON_OBJECT('id',users.id,'firstName', users.name,'lastName',users.surname,'photo',users.photo,'status',users.status,'birthDate',DATE_FORMAT(users.birth_date ,'%d.%m.%Y'),'ubirthDate',UNIX_TIMESTAMP(users.birth_date),'sex',users.sex,'lastVisit',DATE_FORMAT(users.last_visit,'%d.%m.%Y %H:%i'),'ulastVisit',UNIX_TIMESTAMP(users.last_visit))) FROM likedposts INNER JOIN users ON likedposts.user_id = users.id WHERE `post_id` = '$postId'");
        $json = [];
        while ($i = $response->fetch_array()) {
            $json[] = json_decode($i[0], true);
        }
        return $json;
    }

    public function LikeAdd2(int $id, int $postId)
    {
        $stmt = $this->mysqli->prepare('INSERT INTO `likedposts` (`id`, `post_id`, `user_id`) VALUES (NULL,?,?) ON DUPLICATE KEY UPDATE id=`id`');
        $stmt->bind_param('ii', $postId, $id);
        $stmt->execute();
    }

    public function LikeDel2(int $id, int $postId)
    {
        $stmt = $this->mysqli->prepare('DELETE FROM `likedposts` WHERE `post_id` = ? AND `user_id` = ?');
        $stmt->bind_param('ii', $postId, $id);
        $stmt->execute();
    }
    public function StatusGet(int $id)
    {
        $response = $this->mysqli->query("SELECT `status` FROM `users` WHERE `users`.`id` = '$id'");
        $response = $response->fetch_assoc();
        return $response;
    }
    public function StatusSet(int $id, string $text)
    {
        try {
            $text = htmlentities(strip_tags($text), ENT_QUOTES, 'UTF-8');
            $text = str_replace('&nbsp;', ' ', $text);
            $text = html_entity_decode($text);
            $text = trim($text);

            $stmt = $this->mysqli->prepare('UPDATE `users` SET `status` = ? WHERE `users`.`id` = ?');
            $stmt->bind_param('si', $text, $id);
            $stmt->execute();

            return ['status' => $text];
        } catch (Error $e) {
            return json_encode(['message' => 'Internal Server Error', 'error' => 500]);
        }
    }
    public function PhotoGet(int $id)
    {
        $response = $this->mysqli->query("SELECT photo FROM users WHERE id='$id'");
        $json     = [];
        while ($user = $response->fetch_assoc()) {
            $json = $user;
        }
        if ($json['photo']) {
            $filename = $_SERVER['DOCUMENT_ROOT'] . '/avatars/' . $json['photo'];
            $data = getimagesize($filename);
            $json['width'] = $data[0];
            $json['height'] = $data[1];
            return $json;
        }
    }
    public function Photo(int $id, $photo)
    {
        $photo  = strip_tags($photo);
        $photo2 = str_replace('temp', '', $photo);
        rename($_SERVER['DOCUMENT_ROOT'] . "/avatars/$photo", $_SERVER['DOCUMENT_ROOT'] . "/avatars/$photo2");
        $response = $this->mysqli->query("SELECT photo FROM users WHERE id='$id'");
        $json     = [];
        while ($user = $response->fetch_assoc()) {
            $json = $user;
        }
        $oldPhoto = $json['photo'];
        unlink($_SERVER['DOCUMENT_ROOT'] . "/avatars/min/$oldPhoto");
        unlink($_SERVER['DOCUMENT_ROOT'] . "/avatars/mid/$oldPhoto");
        unlink($_SERVER['DOCUMENT_ROOT'] . "/avatars/$oldPhoto");
        $stmt = $this->mysqli->prepare('UPDATE `users` SET `photo` = ? WHERE `users`.`id` = ?');
        $stmt->bind_param('si', $photo2, $id);
        $stmt->execute();

        require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/avatar.php';
        avatarResize($photo2);
        return $response;
    }
    public function PhotoSet(int $id, mixed $photo)
    {
        if (isset($photo)) {
            //$ext  = pathinfo($photo['name'], PATHINFO_EXTENSION);
            //$tempName =  'temp' . time() . '.'. $ext;
            $stmt = $this->mysqli->prepare("SELECT `photo` FROM `users` WHERE `users`.`id` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $response = $stmt->get_result();
            $json     = [];
            while ($user = $response->fetch_assoc()) {
                $json = $user;
            }
            $oldPhoto = $json['photo'];
            @unlink(DIR_BRB_CONTENT . "/avatars/min/$oldPhoto");
            @unlink(DIR_BRB_CONTENT . "/avatars/mid/$oldPhoto");
            @unlink(DIR_BRB_CONTENT . "/avatars/$oldPhoto");
            //$path = $_SERVER['DOCUMENT_ROOT'] . '/avatars/' . $tempName;
            //move_uploaded_file($photo['tmp_name'], $path);
            require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';

            $imageName = strip_tags(avatare($photo['tmp_name']));
            $stmt      = $this->mysqli->prepare("UPDATE `users` SET `photo` = ? WHERE `users`.`id` = ? ");
            $stmt->bind_param('si', $imageName, $id);
            if ($stmt->execute()) return ['photo' => $imageName];
        }
        return false;
    }
    public function StickersGet(int $id)
    {
        $where = ($id != 1 && $id != 3 ? ' AND `Private` = 0' : '');
        $response = $this->mysqli->query("SELECT JSON_ARRAYAGG(JSON_OBJECT('name',Name,'pack',Pack,'icon', Icon, 'amount', Amount)) FROM stickers WHERE Season != 1 $where");
        return json_decode($response->fetch_row()[0], true);
    }
    public function StoriesGet(int $id = null)
    {
        try {
            $id = ($id ? "WHERE user_id = $id" : '');
            $response = $this->mysqli->query("SELECT stories.id, stories.user_id AS userId, UNIX_TIMESTAMP(stories.date) as date, stories.path as video, users.name as firstName, users.surname as lastName ,users.photo AS `photo` FROM `stories` INNER JOIN users ON stories.user_id = users.id $id");
            $json     = [];
            while ($user = $response->fetch_assoc()) {
                $user['video'] = '/stories/' . $user['video'];
                $json[] = $user;
            }
            return $json;
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    public function StoriesSet(int $id, $file)
    {
        if (isset($file)) {
            $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newname = "id$id" . time() . '.' . $ext;
            $stmt    = $this->mysqli->prepare("INSERT INTO `stories` (`id`,`user_id`,`path`) VALUES (NULL,?,?) ON DUPLICATE KEY UPDATE `date`=CURRENT_TIMESTAMP, `path`=?");
            $stmt->bind_param('iss', $id, $newname, $newname);
            $path = $_SERVER['DOCUMENT_ROOT'] . '/stories/' . $newname;
            foreach (glob($_SERVER['DOCUMENT_ROOT'] . "/stories/id$id*") as $filename) {
                unlink($filename);
            }
            move_uploaded_file($file['tmp_name'], $path);
            if ($stmt->execute()) {
                //print_r($stmt->error);
                return $this->StoriesGet($id);
            }
        }
        return false;
    }
    public function attachImg(int $id, $file)
    {
        $name = $file['tmp_name'];
        exec('cd ' . sys_get_temp_dir()  . "/; cwebp -q 100 $name -o $name");
        $this->ftpConnect();
        $name = time();
        if (ftp_nlist($this->conn, "/images/$id") == false) {
            ftp_mkdir($this->conn, "/images/$id");
        }
        if (ftp_put($this->conn, "/images/$id/$name", $file['tmp_name'], FTP_BINARY)) {
            ftp_close($this->conn);
            if ($id < 10) {
                $id = 0 . $id;
            }
            echo $id . $name;
        } else {
            ftp_close($this->conn);
            echo false;
        }
    }
    public function detachImg(int $id, $file)
    {
        $id = substr($file, 0, 1);
        $file = substr($file, 1);
        $this->ftpConnect();
        if (ftp_delete($this->conn, "/images/$id/$file")) {
            ftp_close($this->conn);
            echo true;
        } else {
            ftp_close($this->conn);
            echo false;
        }
    }
    public function LastVisit(int $id)
    {
        $lastVisit = date('Y-m-d H:i:s');
        $this->mysqli->query("UPDATE `users` SET `last_visit` = '$lastVisit' WHERE `users`.`id` = '$id'");
    }
    public function Signin(string $firstName, string $lastName, $birthDate, mixed $photo = null, bool $sex, string $username, string $password)
    {
        $firstName = strip_tags($firstName);
        $lastName = strip_tags($lastName);
        $birthDate = date('Y-m-d', strtotime($birthDate));
        $username = strip_tags($username);
        $password = password_hash(strip_tags($password), PASSWORD_DEFAULT);
        if (mb_strlen($firstName) < 3)       return ['message' => 'Firstname must be at least 2 characters!', 'error' => 500];
        else if (mb_strlen($firstName) > 20) return ['message' => 'Firstname must be less than 20 characters long!', 'error' => 500];

        if (mb_strlen($lastName) < 3)       return ['message' => 'Lastname must be at least 2 characters!', 'error' => 500];
        else if (mb_strlen($lastName) > 20) return ['message' => 'Lastname must be less than 20 characters long!', 'error' => 500];

        if (mb_strlen($username) < 4)       return ['message' => 'Username must be at least 4 characters!', 'error' => 500];
        else if (mb_strlen($username) > 20) return ['message' => 'Username must be less than 20 characters long!', 'error' => 500];

        if (mb_strlen($password) < 4)       return ['message' => 'Password must be at least 4 characters!', 'error' => 500];
        else if (mb_strlen($username) > 50) return ['message' => 'Password must be less than 50 characters long!', 'error' => 500];

        try {
            $login = $this->mysqli->query("SELECT login AS username FROM users WHERE login='$username'");
            $login = $login->fetch_assoc();
            if ($login === null) {
                if ($photo && $photo['tmp_name']) {
                    require_once $_SERVER['DOCUMENT_ROOT'] . '/v2/func.php';
                    $imageName = avatare(strip_tags($photo['tmp_name']));
                } else {
                    $imageName = 'default.png';
                }
                $stmt = $this->mysqli->prepare("INSERT INTO users (name, surname, birth_date, photo, status, sex, login, password) VALUES (?, ?, ?, ?, 'Я - барыбинец!', ?, ?, ?)");
                $stmt->bind_param('ssssiss', $firstName, $lastName, $birthDate, $imageName, $sex, $username, $password);
                $stmt->execute();
                $role = $this->mysqli->insert_id;
                $this->mysqli->query("INSERT INTO user_roles (user_id, role_id) VALUES ($role, '4')");

                if ($stmt->execute()) return ['photo' => $imageName];
            } else {
                return ['message' => 'Username already exists!', 'error' => 500];
            }
        } catch (mysqli_sql_exception $e) {
            return json_encode(['message' => 'Internal Server Error', 'error' => 500]);
        }

        return true;
    }
    public function Login(string $username, string $password)
    {
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/BeforeValidException.php';
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/ExpiredException.php';
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/SignatureInvalidException.php';
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/JWT.php';
        try {
            $result   = $this->mysqli->query("SELECT users.id AS 'userId', `login` AS `usr_login`, `password` AS `usr_pass`, `name` AS 'firstName', `surname` AS 'lastName', `photo`, `status`, DATE_FORMAT(birth_date ,'%d.%m.%Y') AS birthDate, UNIX_TIMESTAMP(users.birth_date) AS ubirthDate, `sex`, DATE_FORMAT(last_visit,'%d.%m.%Y %H:%i') AS lastVisit, UNIX_TIMESTAMP(last_visit) AS ulastVisit, user_roles.role_id AS roleId  FROM users INNER JOIN user_roles ON user_roles.user_id=users.id WHERE login='$username'");
            $row      = $result->fetch_assoc();
            $response = [];
            if ($result->num_rows == 0) {
                $response = ['error' => 'Forbidden', 'message' => 'Access Denied', 'code' => 403];
            } elseif (!password_verify($password, $row['usr_pass'])) {
                $response = ['error' => 'Forbidden', 'message' => 'Access Denied', 'code' => 403];
            } elseif (password_verify($password, $row['usr_pass'])) {
                //ip
                $this->mysqli->query("UPDATE `users` SET `ip` = '{$_SERVER['REMOTE_ADDR']}' WHERE `users`.`login` = '{$row['usr_login']}'");
                //
                $nowTime = time();
                $token = [
                    'iss' => 'Barybians',
                    'aud' => $row['userId'],
                    'iat' => $nowTime,
                    //'nbf' => $nowTime + 2,
                    'exp' => $nowTime + (60 * 60 * 24 * 90)
                ];
                $jwt = JWT::encode($token, 'JWT_BRB_KEY');
                unset($row['usr_pass'], $row['usr_login']);
                //$row['userId']  =  intval($row['userId']);
                $response['user']  = $row;
                $response['token'] = $jwt;
                $response['code'] = 200;
            }
            return $response;
        } catch (mysqli_sql_exception $e) {
            //exit($e);
            return 'Error!!!';
        }
    }
    public function NotifyGet(int $id)
    {
        try {
            $result = $this->mysqli->query("SELECT COUNT(`id`) as 'count' FROM `messages` WHERE `messages`.`reciever_id` = '{$id}' AND `messages`.`unread`= 1");
            return ["unreadedMessages" => $result->fetch_assoc()['count']];
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    public function boilerSet(string $name, mixed $file)
    {
        $this->ftpConnect();
        $filename = time() . '.mp3';
        if (ftp_put($this->conn, "/boiler/$filename", $file['tmp_name'], FTP_BINARY)) echo true;
        ftp_close($this->conn);

        try {
            $stmt = $this->mysqli->prepare("INSERT INTO boiler (id, name, mp3) VALUES (null, ?, ?)");
            $stmt->bind_param('ss', $name, $filename);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return json_encode(['message' => 'Internal Server Error', 'error' => 500]);
        }

        return "https://legacy.barybians.ru/boiler/$filename";
    }
    public function boilerGet()
    {
        try {
            $response = $this->mysqli->query("SELECT name, mp3 FROM boiler");
            $json     = [];
            while ($tape = $response->fetch_assoc()) {
                $tape['mp3'] = 'https://legacy.barybians.ru/boiler/' . $tape['mp3'];
                $json[] = $tape;
            }
            return $json;
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    public function boilerUpdate(string $name, string $mp3)
    {
        try {
            $stmt = $this->mysqli->prepare('UPDATE `boiler` SET `name` = ? WHERE `boiler`.`mp3` = ?');
            $stmt->bind_param('ss', $name, $mp3);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return false;
        }
        return true;
    }
    public function boilerDelete(string $mp3)
    {
        try {
            $stmt = $this->mysqli->prepare('DELETE FROM `boiler` WHERE `boiler`.`mp3` = ?');
            $stmt->bind_param('s', $mp3);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return false;
        }
        $this->ftpConnect();
        if (@ftp_delete($this->conn, "/boiler/$mp3")) {
            ftp_close($this->conn);
            return true;
        } else {
            ftp_close($this->conn);
            return false;
        }
    }
}
