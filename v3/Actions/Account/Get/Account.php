<?php
class GetAccount
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $model = new UsersModel();
        $user = $model->getUsers($props->tokenOwner, $props->tokenOwner);
        return $user;
    }
}
