<?php
class SetLikes
{
    public function Action($props)
    {
        require_once(PATH . '/Models/LikesModel.php');

        $model = new LikesModel($props->tokenOwner);
        $postId = (int) $props->uri[0] ?? 0;
        $like = $model->setPostLike($props->tokenOwner, $postId);

        $likes =  $model->getPostLikes($postId, 0, 0, 25); //$like['id']

        return $likes;
    }
}
