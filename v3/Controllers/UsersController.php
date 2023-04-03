<?php

/** http://localhost/users **/
class Users extends Api
{
    protected string $apiController = __CLASS__;
    /*****************************************************
     * Users
     *****************************************************/
    /* 
     * Вывод списка всех пользователей
     * GET
     * http://localhost/users
     */
    protected function GetUsersAction()
    {
        include_once(PATH . '/Actions/Users/Get/Users.php');
        $action = new GetUsers();
        return $action->Action($this);
    }
    /*
     * Вывод пользователя по id
     * GET
     * http://localhost/users/{userId}
     */
    protected function GetUserAction()
    {
        include_once(PATH . '/Actions/Users/Get/User.php');
        $action = new GetUser();
        return $action->Action($this);
    }
    /*****************************************************
     * Posts
     *****************************************************/
    /* 
     * Вывод постов пользователя
     * GET
     * http://localhost/users/{id}/posts
     */
    protected function GetUserPostsAction()
    {
        include_once(PATH . '/Actions/Users/Get/Posts.php');
        $action = new GetUserPosts();
        return $action->Action($this);
    }
    /*****************************************************
     * Messages
     *****************************************************/
    /*
     * Вывод сообщений
     * GET
     * http://localhost/users/{userId}/messages
     */
    protected function GetUserMessagesAction()
    {
        include_once(PATH . '/Actions/Users/Get/Messages.php');
        $action = new GetUserMessages();
        return $action->Action($this);
    }
    /*
     * Вывод сообщения
     * GET
     * http://localhost/users/{userId}/messages/{messageId}
     */
    protected function GetUserMessageAction()
    {
        include_once(PATH . '/Actions/Users/Get/Message.php');
        $action = new GetUserMessage();
        return $action->Action($this);
    }
    /*
     * Отправить сообщение пользователю
     * POST
     * http://localhost/users/{userId}/messages
     * @param tokenOwnerId
     * @param secondUserId
     * @param text
     */
    protected function SetUserMessagesAction()
    {
        include_once(PATH . '/Actions/Users/Post/Messages.php');
        $action = new SetUserMessages();
        return $action->Action($this);
    }
}
