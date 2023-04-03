<?php
class DialogsModel extends Database
{
  public function getDialogs(int $firstId = 0, int $secondId = 0)
  {
    require_once(PATH . '/Models/UsersModel.php');
    require_once(PATH . '/Models/MessagesModel.php');
    $users = new UsersModel();
    $messages = new MessagesModel();


    $groupBy = '';
    if (!$secondId) $groupBy = 'GROUP BY messages.id, messages.time';
    else $groupBy = 'GROUP BY messages.id, users.id';
    $array  = (int)!$secondId ? 'JSON_ARRAYAGG' : '';
    (int) $secondId = $secondId ? $secondId : 'users.id';
    $where = (int) $secondId ? " WHERE userId = $secondId " : '';



    return $this->select("SELECT DISTINCT 
    $array( 
      JSON_OBJECT(
        'secondUser', 
        {$users->UsersJson()}, 
        'lastMessage', 
        JSON_OBJECT({$messages->JSON}),
        'unreadCount', 
        unreadCount,
        'messagesCount',
        messagesCount
      )
    ) 
  FROM 
   ( SELECT * from(
      (
        SELECT DISTINCT
          {$messages->table1},
          {$users->UsersTable()},
          (SELECT distinct count(*) from messages WHERE messages.sender_id = $secondId AND messages.reciever_id = '$firstId' AND messages.unread = 1) as unreadCount,
          (SELECT distinct SUM(msgs) from ((SELECT count(messages.id) as msgs from messages where messages.sender_id = $secondId AND messages.reciever_id = '$firstId')
            union distinct (SELECT count(messages.id) as msgs from messages where messages.reciever_id = $secondId AND messages.sender_id = '$firstId')) as temp) as messagesCount
        FROM messages 
          INNER JOIN users ON(messages.sender_id = $secondId) {$users->UsersPostsTable()}
          WHERE messages.sender_id != messages.reciever_id AND messages.id = greatest(COALESCE((SELECT max(id) from messages where sender_id = $secondId and reciever_id = '$firstId'),0),
            COALESCE((SELECT max(id) from messages where reciever_id = $secondId and sender_id = '$firstId'),0))
          $groupBy
      )
      union distinct
      (
        SELECT DISTINCT
          {$messages->table1},
          {$users->UsersTable()},
          (SELECT distinct count(*) from messages WHERE messages.sender_id = '$firstId' AND messages.reciever_id = $secondId AND messages.unread = 1) as unreadCount,
          (SELECT distinct SUM(msgs) from ((SELECT count(messages.id) as msgs from messages where messages.sender_id = $secondId AND messages.reciever_id = '$firstId')
            union distinct (SELECT count(messages.id) as msgs from messages where messages.reciever_id = $secondId AND messages.sender_id = '$firstId')) as temp) as messagesCount
        FROM messages 
          INNER JOIN users ON(messages.reciever_id = $secondId) {$users->UsersPostsTable()}
          WHERE messages.sender_id != messages.reciever_id AND messages.id = greatest(COALESCE((SELECT max(id) from messages where sender_id = $secondId and reciever_id = '$firstId'),0),
            COALESCE((SELECT max(id) from messages where reciever_id = $secondId and sender_id = '$firstId'),0))
          $groupBy
      )
    ) AS temp group by temp.firstName, temp.lastName, temp.photo,temp.status,temp.birthDate,temp.sex,temp.role,temp.lastVisit,temp.senderId,temp.receiverId,temp.text,temp.attachments,temp.time,temp.unread,temp.unreadCount, temp.messageId, temp.userId, temp.postsCount, temp.messagesCount order by time desc) AS union1 
    $where
    #GROUP BY union1.firstName, union1.lastName, union1.photo,union1.status,union1.birthDate,union1.sex,union1.lastVisit,union1.senderId,union1.receiverId,union1.text,union1.attachments,union1.time,union1.unread,union1.unreadCount
  
    #ORDER BY union1.time DESC
    ;
  ");
  }

  public function getNotifications(int $userId)
  {
    return $this->select("SELECT JSON_OBJECT('unreadedMessages', COUNT(`id`)) FROM `messages` WHERE `messages`.`reciever_id` = '$userId' AND `messages`.`unread`= 1;");
  }
}
