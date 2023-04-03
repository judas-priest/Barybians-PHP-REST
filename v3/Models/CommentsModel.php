<?php
class CommentsModel extends Database
{
    public function getComments(int $postId = 0, int $commentId = 0, int $offset = 0, int $limit = 25, bool $descending = true)
    {
        require_once(PATH . '/Models/UsersModel.php');
        $users = new UsersModel();
        $desc = ($descending  ? 'DESC' : '');
        $array  = !$commentId ? 'JSON_ARRAYAGG' : '';
        $groupBy = '';
        if ($commentId) $groupBy = 'GROUP BY commentId';


        $where = '';

        if ($postId && $commentId) {
            $where = "where comments.post_id = $postId AND comments.id = $commentId";
        } elseif ($postId && !$commentId) {
            $where = "where comments.post_id = $postId";
        } elseif (!$postId && $commentId) {
            $where = "where comments.id = $commentId";
        }



        return $this->select("SELECT DISTINCT
        $array(JSON_OBJECT('commentId',commentId,'userId',commentUser,'text',commentText,
        'attachments',if(commentAttachments is not null,commentAttachments,JSON_ARRAY()),'time',commentTime,
        'lastModified',commentLastEdited,'userId', commentUser, 'postId', postId,
        'author',{$users->UsersJson()}))
        from
        (SELECT DISTINCT comments.id as commentId, comments.post_id as postId, comments.user_id as commentUser, comments.text as commentText, comments.attachment as commentAttachments,
        UNIX_TIMESTAMP(comments.time) as commentTime, UNIX_TIMESTAMP(comments.last_edit) as commentLastEdited,
        {$users->UsersTable()}
            FROM comments
            LEFT JOIN users ON comments.user_id = users.id
            {$users->UsersPostsTable()}
            $where
            GROUP BY commentId
            ORDER BY commentId $desc LIMIT $offset, $limit
            )
        as comments
        $groupBy;");
    }

    public function setComment(int $userId, int $postId, string $text, string $attachments)
    {
        return $this->insert(
            'INSERT INTO `comments` (`id`, `post_id`, `user_id`, `text`, `attachment`) VALUES (NULL,?,?,?,?);',
            [$postId, $userId, $text, $attachments]
        );
    }
    public function editComment(int $userId, int $postId, int $commentId, string $text, string $attachments)
    {
        return $this->insert(
            'UPDATE `comments` SET `text` = ?, `edited` = "1", `last_edit` = CURRENT_TIMESTAMP, `attachment` = ? WHERE `comments`.`id` = ? AND `comments`.`user_id`= ? AND `comments`.`post_id`= ?;',
            [$text, $attachments, $commentId, $userId, $postId]
        );
    }

    public function delComment(int $userId, int $postId, int $commentId)
    {
        return $this->insert(
            'DELETE FROM `comments` WHERE `comments`.`user_id` = ? AND `comments`.`id` = ? AND `comments`.`post_id` = ?;',
            [$userId, $commentId, $postId]
        );
    }
}
