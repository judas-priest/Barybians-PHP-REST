<?php

class GetUserMessage
{
    public function Action($props)
    {
        require_once(PATH . '/Models/MessagesModel.php');

        $firstId = $props->tokenOwner;
        $secondId = (int) $props->uri[0] ?? 0;
        $messageId = (int) $props->uri[2] ?? 0;

        if (!$secondId) return $props->error('secondId must exist and be an integer', 404);


        $model = new MessagesModel();
        $message = $model->getUserMessages($firstId, $secondId, $messageId);

        return $message;
    }
}
