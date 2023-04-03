<?php
class SetPosts
{
    public function Action($props)
    {
        require_once(PATH . '/Models/PostsModel.php');
        require_once(PATH . '/Methods/Parse.php');

        $text = $props->param('text');
        $title = $props->param('title');

        $tempParse = Parse($text, @$props->headers['parse-mode'] ?? 'text');
        $text = $tempParse[0];
        $attachments = $tempParse[1];


        $model = new PostsModel($props->tokenOwner);
        $post = $model->setPost($props->tokenOwner, $title, $text, $attachments);

        $newPost = $model->getPosts($props->tokenOwner, $post['id']);


        return $newPost;
    }
}
