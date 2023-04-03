<?php

/** http://localhost/stickers **/
class Stickers extends Api
{
    protected string $apiController = __CLASS__;
    /* 
     * Вывод списка всех стикер паков
     * GET
     * http://localhost/stickers
     */
    protected function GetStickersAction()
    {
        include_once(PATH . '/Actions/Stickers/Get/Stickers.php');
        $action = new GetStickers();
        return $action->Action($this);
    }
    /* 
     * Вывод конкретного стикер пака
     * GET
     * http://localhost/stickers/{id}
     */
    /*
    protected function GetStickerAction()
    {
        $stickerPack = (string) $this->uri[0];
        $model = new StickersModel($this->tokenOwner);
        $sticker = $model->getStickerPack($this->tokenOwner, $stickerPack);

        switch ($sticker['status']) {
            case 200:
                return $this->response($sticker['json']);
                break;
            case 404:
                return $this->error("sticker pack not found", 404);
                break;
            default:
                return $this->error();
                break;
        }
    }
    */
}
