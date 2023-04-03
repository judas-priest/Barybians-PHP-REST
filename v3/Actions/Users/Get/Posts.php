<?php
class GetUserPosts
{
    public function Action($props)
    {
        require_once(PATH . '/Models/PostsModel.php');

        $offset = $props->param('offset');
        $desc = $props->param('desc');

        $userId = (int) $props->uri[0] ?? 0;
        $model = new PostsModel($props->tokenOwner);
        $posts =  $model->getPosts($userId, 0, $offset, 25, $desc);

        return $posts;
    }
}
