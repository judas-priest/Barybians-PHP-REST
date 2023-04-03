<?php

class SetStatus
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $text = $props->param('text');

        $model = new UsersModel();
        $model->setUserStatus($props->tokenOwner, $text);
        $status = $model->getUserStatus($props->tokenOwner);

        return $status;
    }
}
