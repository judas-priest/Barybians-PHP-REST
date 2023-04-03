<?php
class GetStickers
{
    public function Action($props)
    {
        require_once(PATH . '/Models/StickersModel.php');
        $model = new StickersModel($props->tokenOwner);
        $stickers = $model->getStickerPack($props->tokenOwner);

        return $stickers;
    }
}
