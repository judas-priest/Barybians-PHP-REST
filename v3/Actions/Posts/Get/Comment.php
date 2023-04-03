<?php
class GetComment
{
    public function Action($props)
    {
        require_once(PATH . '/Models/CommentsModel.php');

        $model = new CommentsModel($props->tokenOwner);

        $postId = (int) $props->uri[0] ?? 0;
        $commentId = (int) $props->uri[2] ?? 0;

        $comments =  $model->getComments($postId, $commentId);

        return $comments;
    }
}
