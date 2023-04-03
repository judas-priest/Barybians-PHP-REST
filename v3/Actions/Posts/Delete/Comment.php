<?php
class DelComment
{
    public function Action($props)
    {
        require_once(PATH . '/Models/CommentsModel.php');
        $model = new CommentsModel($props->tokenOwner);

        $postId = (int) $props->uri[0] ?? 0;
        $commentId = (int) $props->uri[2] ?? 0;

        $comment = $model->delComment($props->tokenOwner, $postId, $commentId);

        if ($comment['status']) return ['message' => 'comment deleted', 'error' => 200];
        else return ['message' => 'an error occurred while deleting a comment', 'error' => 500];
    }
}
