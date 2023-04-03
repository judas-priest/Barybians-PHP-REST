<?php

class GetStatus
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $model = new UsersModel();
        $status = $model->getUserStatus($props->tokenOwner);

        return $status;
    }
}
