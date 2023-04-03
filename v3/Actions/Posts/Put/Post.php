<?php
class EditPost
{
    public function Action($props)
    {
        require_once(PATH . '/Models/PostsModel.php');
        require_once(PATH . '/Methods/Parse.php');

        $postId = (int) $props->uri[0] ?? 0;

        $text = $props->param('text');
        $title = $props->param('title');

        $tempParse = Parse($text, @$props->headers['parse-mode'] ?? 'text');
        $text = $tempParse[0];
        $attachments = $tempParse[1];


        $model = new PostsModel($props->tokenOwner);
        $model->editPost($props->tokenOwner, $postId, $title, $text, $attachments);

        $newPost = $model->getPosts($props->tokenOwner, $postId);


        return $newPost;
    }
}
