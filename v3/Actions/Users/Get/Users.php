<?php
class GetUsers
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $model = new UsersModel();
        $online = $props->param('online');
        $users = $model->getUsers(0, $props->tokenOwner, $online);
        return $users;
    }
}
