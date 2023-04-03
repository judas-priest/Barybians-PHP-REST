<?php
class GetPosts
{
    public function Action($props)
    {
        require_once(PATH . '/Models/PostsModel.php');

        $offset = $props->param('offset');
        $desc = $props->param('desc');

        $model = new PostsModel($props->tokenOwner);
        $posts = $model->getPosts(0, 0, $offset, 25, $desc);

        return $posts;
    }
}
