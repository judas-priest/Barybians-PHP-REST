<?php
class EditComment
{
    public function Action($props)
    {
        require_once(PATH . '/Models/CommentsModel.php');
        require_once(PATH . '/Methods/Parse.php');

        $text = $props->param('text');

        $postId = (int) $props->uri[0] ?? 0;
        $commentId = (int) $props->uri[2] ?? 0;


        $tempParse = Parse($text, @$props->headers['parse-mode'] ?? 'text');
        $text = $tempParse[0];
        $attachments = $tempParse[1];

        $model = new CommentsModel($props->tokenOwner);
        $comment = $model->editComment($props->tokenOwner, $postId, $commentId, $text, $attachments);

        $newComment = $model->getComments($postId, $commentId);

        return $newComment;
    }
}
