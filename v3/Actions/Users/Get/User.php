<?php
class GetUser
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $userId = (int)@$props->uri[0] ?? 0;
        $model = new UsersModel();
        $user = $model->getUsers($userId, $props->tokenOwner);


        if ($user['json']) return $user;
        else return ['message' => 'user not found', 'error' => 404];
    }
}
