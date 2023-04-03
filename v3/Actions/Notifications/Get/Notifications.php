<?php
class GetNotifications
{
    public function Action($props)
    {
        require_once(PATH . '/Models/DialogsModel.php');
        $id = (int) $props->tokenOwner ?? 0;
        $model = new DialogsModel($props->tokenOwner);
        $dialogs = $model->getNotifications($id);

        return $dialogs;
    }
}
