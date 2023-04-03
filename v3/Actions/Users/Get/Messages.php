<?php
class GetUserMessages
{
    public function Action($props)
    {
        require_once(PATH . '/Models/MessagesModel.php');

        $offset = $props->param('offset');
        $unread = $props->param('unread');
        $desc = $props->param('desc');

        $firstId = $props->tokenOwner;
        $secondId = (int) $props->uri[0] ?? 0;

        if (!$secondId) return $props->error('secondId must exist and be an integer', 404);


        $model = new MessagesModel();
        $messages = $model->getUserMessages($firstId, $secondId, 0, $offset, 10, $unread, $desc);
        $model->setUnreadReadMessages($firstId, $secondId);

        return $messages;
    }
}
