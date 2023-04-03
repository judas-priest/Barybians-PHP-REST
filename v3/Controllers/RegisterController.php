<?php

/** http://localhost/register **/

class Register extends Api
{
    protected string $apiController = __CLASS__;
    /* 
     * Регистрация
     * POST
     * http://localhost/register
     * @param firstname
     * @param lastname
     * @param birthdate
     * @param sex
     * @param username
     * @param password
     */
    protected function SetRegisterAction()
    {
        include_once(PATH . '/Actions/Register/Post/Register.php');
        $action = new SetRegister();
        return $action->Action($this);
    }
}
