<?php
class GetPost
{
    public function Action($props)
    {
        require_once(PATH . '/Models/PostsModel.php');

        $postId = (int) $props->uri[0] ?? 0;
        $model = new PostsModel($props->tokenOwner);
        $post = $model->getPosts(0, $postId);

        return $post;
    }
}
