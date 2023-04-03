<?php

/** http://localhost/auth **/

class Auth extends Api
{
    protected string $apiController = __CLASS__;
    /* 
     * Авторизация
     * POST
     * http://localhost/auth
     * @param username
     * @param password
     */
    protected function SetAuthAction()
    {
        include_once(PATH . '/Actions/Auth/Post/Auth.php');
        $action = new SetAuth();
        return $action->Action($this);
    }
}
