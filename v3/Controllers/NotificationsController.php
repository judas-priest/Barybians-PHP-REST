<?php

/** http://localhost/notifications **/
class Notifications extends Api
{
    protected string $apiController = __CLASS__;
    /* 
     * Уведомления
     * GET
     * http://localhost/notifications
     */
    protected function GetNotificationsAction()
    {
        include_once(PATH . '/Actions/Notifications/Get/Notifications.php');
        $action = new GetNotifications();
        return $action->Action($this);
    }
}
