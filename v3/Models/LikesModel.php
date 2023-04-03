<?php
class LikesModel extends Database
{
    public function getPostLikes(int $postId = 0, int $likeId = 0, int $offset = 0, int $limit = 25, bool $desc = true)
    {

        require_once(PATH . '/Models/UsersModel.php');
        $users = new UsersModel();
        $desc = ($desc  ? 'DESC' : '');
        $array  = !$likeId ? 'JSON_ARRAYAGG' : '';
        $groupBy = '';
        if ($likeId) $groupBy = 'GROUP BY likeId';


        return $this->select("SELECT DISTINCT
            $array(JSON_OBJECT('likeId',likeId,'userId',likerUser,'postId',postId,
            'author',{$users->UsersJson()}))
            from
            (SELECT DISTINCT likedposts.id as likeId, likedposts.post_id as postId, likedposts.user_id as likerUser,
            {$users->UsersTable()}
                FROM likedposts
                LEFT JOIN users ON likedposts.user_id = users.id
                {$users->UsersPostsTable()}
                where likedposts.post_id = $postId
                GROUP BY likeId
                ORDER BY likeId $desc LIMIT $offset, $limit
                )
            as likes
            $groupBy;");
    }

    public function setPostLike(int $userId, int $postId)
    {
        return $this->insert(
            'INSERT INTO `likedposts` (`id`, `post_id`, `user_id`) VALUES (NULL,?,?) ON DUPLICATE KEY UPDATE id=`id`;',
            [$postId, $userId]
        );
    }
    public function delPostLike(int $userId, int $postId)
    {
        return $this->insert(
            'DELETE FROM `likedposts` WHERE `post_id` = ? AND `user_id` = ?;',
            [$postId, $userId]
        );
    }
}
