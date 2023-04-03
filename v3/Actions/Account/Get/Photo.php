<?php

class GetPhoto
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $model = new UsersModel();
        $photo = $model->getUserPhoto($props->tokenOwner);

        return $photo;
    }
}
