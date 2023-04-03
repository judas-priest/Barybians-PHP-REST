<?php
class StickersModel extends Database
{
    public function getStickerPack(int $id, int $stickerPack = 0)
    {
        $stickerPack = ($stickerPack  ? $stickerPack : '');
        $array  = (int) !$stickerPack ? 'JSON_ARRAYAGG' : '';

        $where = ($id != 1 && $id != 3 ? 'AND `Private` = 0' : '');
        //$where2 = ($stickerPack ? "AND name = $stickerPack" : '');


        return $this->select("SELECT $array(JSON_OBJECT('name',Name,'pack',Pack,'icon', Icon, 'amount', Amount))
        FROM stickers
        WHERE Season != 1 $where;");
    }
}
