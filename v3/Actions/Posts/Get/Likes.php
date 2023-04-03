<?php
class GetLikes
{
    public function Action($props)
    {
        require_once(PATH . '/Models/LikesModel.php');

        $offset = $props->param('offset');
        $descending = $props->param('desc');

        $model = new LikesModel($props->tokenOwner);
        $postId = (int) $props->uri[0] ?? 0;
        $likes =  $model->getPostLikes($postId, 0, $offset, 25, $descending);

        return $likes;
    }
}
