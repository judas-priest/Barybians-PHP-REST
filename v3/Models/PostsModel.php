<?php
class PostsModel extends Database
{
    public function getPosts(int $userId = 0, int $postId = 0, int $offset = 0, int $limit = 25, bool $descending = true)
    {
        require_once(PATH . '/Models/UsersModel.php');
        $users = new UsersModel();

        $where = '';

        if ($postId && $userId) {
            $where = "WHERE posts.user_id = $userId AND posts.id = $postId";
        } elseif ($postId && !$userId) {
            $where = "WHERE posts.id = $postId";
        } elseif (!$postId && $userId) {
            $where = "WHERE posts.user_id = $userId";
        }

        $desc = ($descending  ? 'DESC' : '');
        $array  = !$postId ? 'JSON_ARRAYAGG' : '';
        $groupBy = '';
        if ($postId) $groupBy = 'GROUP BY postId';


        return $this->select("SELECT if(COUNT(postId) =
        0,json_array(),
        $array(JSON_OBJECT('postId',postId,'title',postTitle,'userId',postUser,'text',postText,'attachments',postsAttachments,'time',postTime,'lastModified',postLastEdited,
        'commentsCount', commentsCount, 'likesCount', likesCount,
        'author',({$users->UsersJson()})
        )))
        FROM
        (SELECT DISTINCT posts.id as postId,
        posts.title as postTitle,
        posts.text as postText,
        posts.user_id as postUser,
        posts.attachment as postsAttachments,
        UNIX_TIMESTAMP(posts.time) as postTime,
        UNIX_TIMESTAMP(posts.last_edit) as
        postLastEdited,posts.edited as postEdited,
        if(comments.count is not null, comments.count, 0) as commentsCount,
        if(likes.count is not null, likes.count, 0) as likesCount,
        {$users->UsersTable()}
        FROM posts

        LEFT JOIN (SELECT DISTINCT COUNT(comments.post_id) as count, post_id from comments GROUP BY comments.post_id) as comments ON comments.post_id = posts.id
        LEFT JOIN (SELECT DISTINCT COUNT(likedposts.post_id) as count, post_id from likedposts GROUP BY likedposts.post_id) as likes ON likes.post_id = posts.id
        INNER JOIN users ON posts.user_id = users.id
        {$users->UsersPostsTable()}
        $where
        
        
        GROUP BY postId
        ORDER BY postId $desc LIMIT $offset, $limit
        ) as json
        
        $groupBy;");
    }
    public function setPost(int $userId, string $title, string $text, string $attachments)
    {
        return $this->insert(
            'INSERT INTO `posts` (`user_id`,`title`,`text`,`attachment`) VALUES(?,?,?,?);',
            [$userId, $title, $text, $attachments]
        );
    }

    public function editPost(int $userId, int $postId, string $title, string $text, string $attachments)
    {
        return $this->insert(
            'UPDATE `posts` SET `title` = ?, `text` = ?, `edited` = "1", `last_edit` = CURRENT_TIMESTAMP, `attachment` = ? WHERE `posts`.`id` = ? AND `posts`.`user_id`= ?;',
            [$title, $text, $attachments, $postId, $userId]
        );
    }

    public function delPost(int $userId, int $postId)
    {
        return $this->insert(
            'DELETE FROM `posts` WHERE `posts`.`user_id` = ? AND `posts`.`id` = ?;',
            [$userId, $postId]
        );
    }
}
