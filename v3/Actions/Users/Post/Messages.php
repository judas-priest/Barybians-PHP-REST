<?php
class SetUserMessages
{

    public function Action($props)
    {
        require_once(PATH . '/Models/MessagesModel.php');
        require_once(PATH . '/Methods/Parse.php');

        $text = $props->param('text');

        $firstId = $props->tokenOwner;
        $secondId = (int) $props->uri[0] ?? 0;

        $tempParse = Parse($text, @$props->headers['parse-mode'] ?? 'text');
        $text = $tempParse[0];
        $attachments = $tempParse[1];


        $model = new MessagesModel($firstId);
        $message = $model->setMessage($firstId, $secondId, $text, $attachments);
        $model->setUnreadReadMessages($firstId, $secondId);

        $newMessage = $model->getUserMessages($firstId, $secondId, $message['id']);

        return $newMessage;
    }
}
