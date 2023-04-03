<?php
class GetDialog
{
    public function Action($props)
    {
        require_once(PATH . '/Models/DialogsModel.php');
        $firstId = (int) $props->tokenOwner ?? 0;
        $secondId = (int) $props->uri[0] ?? 0;

        if (!$firstId || !$secondId) return ['message' => 'secondId must exist and be an integer', 'error' => 400];

        $model = new DialogsModel($props->tokenOwner);
        $dialog = $model->getDialogs($firstId, $secondId);

        return $dialog;
    }
}
