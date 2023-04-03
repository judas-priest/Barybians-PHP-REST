<?php

/** http://localhost/account **/
class Account extends Api
{
    protected string $apiController = __CLASS__;
    /* 
     * Ваш аккаунт
     * GET
     * http://localhost/account
     */
    protected function GetAccountAction()
    {
        include_once(PATH . '/Actions/Account/Get/Account.php');
        $action = new GetAccount();
        return $action->Action($this);
    }
    /* 
     * Ваш статус
     * GET
     * http://localhost/account/status
     */
    protected function GetStatusAction()
    {
        include_once(PATH . '/Actions/Account/Get/Status.php');
        $action = new GetStatus();
        return $action->Action($this);
    }
    /* 
     * Установить статус
     * POST
     * http://localhost/account/status
     * @param text
     */
    protected function SetStatusAction()
    {
        include_once(PATH . '/Actions/Account/Post/Status.php');
        $action = new SetStatus();
        return $action->Action($this);
    }
    /* 
     * Ваш аватар
     * GET
     * http://localhost/account/photo
     */
    protected function GetPhotoAction()
    {
        include_once(PATH . '/Actions/Account/Get/Photo.php');
        $action = new GetPhoto();
        return $action->Action($this);
    }
    /* 
     * Установить фото
     * POST
     * http://localhost/account/photo
     * @param image
     */
    protected function SetPhotoAction()
    {
        include_once(PATH . '/Actions/Account/Post/Photo.php');
        $action = new SetPhoto();
        return $action->Action($this);
    }
}
