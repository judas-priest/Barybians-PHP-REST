<?php
class DelPost
{
    public function Action($props)
    {
        require_once(PATH . '/Models/PostsModel.php');
        $model = new PostsModel($props->tokenOwner);
        $postId = (int) $props->uri[0] ?? 0;
        $post = $model->delPost($props->tokenOwner, $postId);

        if ($post['status']) return ['message' => 'post deleted', 'error' => 200];
        else return ['message' => 'an error occurred while deleting a post', 'error' => 500];
    }
}
