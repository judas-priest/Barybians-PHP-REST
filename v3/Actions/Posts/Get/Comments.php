<?php
class GetComments
{
    public function Action($props)
    {
        require_once(PATH . '/Models/CommentsModel.php');

        $offset = $props->param('offset');
        $descending = $props->param('desc');

        $model = new CommentsModel($props->tokenOwner);
        $postId = (int) $props->uri[0] ?? 0;
        $comments =  $model->getComments($postId, 0, $offset, 25, $descending);

        return $comments;
    }
}
