<?php
class StoriesModel extends Database
{
    public function getStories(int $id = 0, int $offset = 0, int $limit = 25, bool $viewed = false, bool $descending = true)
    {
        require_once(PATH . '/Models/UsersModel.php');
        $users = new UsersModel();
        $array  = !$id ? 'JSON_ARRAYAGG' : '';
        $where = $id ? "WHERE stories.id = $id" : '';
        $desc = ($descending  ? 'DESC' : '');
        $limit = !$id ? "LIMIT $offset, $limit" : '';

        return $this->select("SELECT 
        $array(JSON_OBJECT('storieId', storieId, 'userId', userId, 'time', time, 'video', video,
        'author',{$users->UsersJson()}))
        from
        (SELECT 
        stories.id as storieId,
        stories.user_id,
        UNIX_TIMESTAMP(stories.date) as time,
        CONCAT('/stories/',stories.path) as video,
        {$users->UsersTable()}
        FROM `stories`
        INNER JOIN users ON stories.user_id = users.id
        {$users->UsersPostsTable()}
        $where
        GROUP BY storieId
        ORDER BY storieId
        $desc
        $limit
        ) as stor;");
    }
}
