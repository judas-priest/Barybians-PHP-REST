<?php
class MessagesModel extends Database
{
    public string $JSON =
    // Users JSON grouping by MySQL
    '"messageId", messageId, "senderId", senderId,
    "receiverId", receiverId,
    "text", text,
    "attachments", attachments,
    "time", time,
    "unread", unread';

    public string $table1 =
    // Users table query
    'messages.id AS messageId,
    messages.sender_id AS senderId,
    messages.reciever_id AS receiverId,
    messages.text AS text,
    UNIX_TIMESTAMP(messages.time) AS time,
    messages.unread AS unread,
    messages.attachment AS attachments';

    public function getUserMessages(int $firstId, int $secondId, int $messageId = 0, int $offset = 0, int $limit = 25, bool $unread = false, bool $descending = true)
    {
        require_once(PATH . '/Models/UsersModel.php');
        $users = new UsersModel();

        $desc = ($descending  ? 'DESC' : '');
        $limit = "LIMIT $offset, $limit";
        $array  = !$messageId ? 'JSON_ARRAYAGG' : '';

        if ($messageId) {
            $where1 = "((messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId') AND messages.id = '$messageId')";
            $where2 = "((messages.reciever_id = '$secondId' AND messages.sender_id = '$firstId') AND messages.id = '$messageId')";
        } else {
            $where1 = ($unread ? "((messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId') AND messages.unread = '1')" : "((messages.sender_id = '$secondId' AND messages.reciever_id = '$firstId'))");
            $where2 = ($unread ? "((messages.reciever_id = '$secondId' AND messages.sender_id = '$firstId') AND messages.unread = '1')" : "((messages.reciever_id = '$secondId' AND messages.sender_id = '$firstId'))");
        }


        return $this->select("SELECT if(COUNT(messageId) =
        0,JSON_OBJECT(),
        JSON_OBJECT('messages',$array(JSON_OBJECT({$this->JSON})),
        'firstUser',JSON_OBJECT('userId',u1.id,'firstName',u1.name,'lastName',u1.surname,'photo',CONCAT('" . AVATARS . "',u1.photo),'photo256',CONCAT('" . AVATARS . "mid/',u1.photo),'photo128',CONCAT('" . AVATARS . "min/',u1.photo),'status',u1.status,'birthDate',UNIX_TIMESTAMP(u1.birth_date),'sex',u1.sex,'lastVisit',UNIX_TIMESTAMP(u1.last_visit)),
        'secondUser',JSON_OBJECT('userId',u2.id,'firstName',u2.name,'lastName',u2.surname,'photo',CONCAT('" . AVATARS . "',u2.photo),'photo256',CONCAT('" . AVATARS . "mid/',u2.photo),'photo128',CONCAT('" . AVATARS . "min/',u2.photo),'status',u2.status,'birthDate',UNIX_TIMESTAMP(u2.birth_date),'sex',u2.sex,'lastVisit',UNIX_TIMESTAMP(u2.last_visit))))
        FROM
                (SELECT distinct * from ((SELECT distinct {$this->table1}
                FROM messages
                WHERE $where1
                ORDER BY messages.id) #$limit
            union distinct
                (SELECT distinct {$this->table1}
                FROM messages
                WHERE $where2
                ORDER BY messages.id) #$limit
            ORDER BY messageId $desc $limit) as temp ORDER BY messageId) as msgs
            INNER JOIN users AS u1 ON ('$firstId'=u1.id)
            INNER JOIN users AS u2 ON ('$secondId'=u2.id)
            ORDER BY messageId;");

        /* full with users
        return $this->select("SELECT
        $array(JSON_OBJECT({$this->JSON},
                           'sender',{$users->UsersModel('sender_')},
                           'receiver',{$users->UsersModel('receiver_')}
            ))
        FROM
                ((SELECT distinct {$this->table1},
                {$users->UsersTable('sender', 'sender_')},
                {$users->UsersTable('receiver', 'receiver_')}
                FROM messages
                INNER JOIN users AS sender ON (messages.sender_id=sender.id)
                INNER JOIN users AS receiver ON (messages.reciever_id=receiver.id)
                {$users->UsersPostsTable('sender', 'sender_')}
                {$users->UsersPostsTable('receiver', 'receiver_')}
                WHERE $where1
                GROUP BY messages.id
                ORDER BY messages.id $desc $limit)
            union distinct
                (SELECT distinct {$this->table1},
                {$users->UsersTable('sender', 'sender_')},
                {$users->UsersTable('receiver', 'receiver_')}
                FROM messages
                INNER JOIN users AS sender ON (messages.sender_id=sender.id)
                INNER JOIN users AS receiver ON (messages.reciever_id=receiver.id)
                {$users->UsersPostsTable('sender', 'sender_')}
                {$users->UsersPostsTable('receiver', 'receiver_')}
                WHERE $where2
                GROUP BY messages.id
                ORDER BY messages.id $desc $limit)
            ORDER BY messageId $desc) as msgs;");
            */
    }
    public function setMessage(int $firstId, int $secondId, string $text, string $attachments)
    {
        return $this->insert(
            'INSERT INTO `messages` (`id`,`sender_id`,`reciever_id`,`text`,`attachment`) VALUES (NULL,?,?,?,?);',
            [$firstId, $secondId, $text, $attachments]
        );
    }

    public function setUnreadReadMessages(int $firstId, int $secondId)
    {
        $this->update(
            'UPDATE `messages` SET `unread` = 0 WHERE `reciever_id` = ? AND `sender_id` = ?;',
            [$firstId, $secondId]
        );
    }
}
