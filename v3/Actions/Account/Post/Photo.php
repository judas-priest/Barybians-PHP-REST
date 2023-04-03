<?php

class SetPhoto
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $newPhoto = $props->param('image');


        function avatare($img)
        {
            $newName = time() . '.webp';
            $image = new Imagick($img);
            $image->setImageFormat('webp');

            $file = DIR_BRB_CONTENT . "/avatars/$newName";
            file_put_contents($file, $image, FILE_APPEND);

            $image->thumbnailImage(128, 128, true);
            $file = DIR_BRB_CONTENT . "/avatars/min/$newName";
            file_put_contents($file, $image, FILE_APPEND);

            $image->thumbnailImage(256, 256, true);
            $file = DIR_BRB_CONTENT . "/avatars/mid/$newName";

            file_put_contents($file, $image, FILE_APPEND);
            unlink($img);
            return $newName;
        }


        if ($newPhoto && $newPhoto['tmp_name']) {
            $photoNewName = avatare($newPhoto['tmp_name']);
        } else {
            $photoNewName = 'default.png';
        }

        $model = new UsersModel();

        $oldPhoto = json_decode($model->getUserPhoto($props->tokenOwner, false)['json'], true)['photo'];
        $model->setUserPhoto($props->tokenOwner, $photoNewName);


        @unlink(DIR_BRB_CONTENT . "/avatars/min/$oldPhoto");
        @unlink(DIR_BRB_CONTENT . "/avatars/mid/$oldPhoto");
        @unlink(DIR_BRB_CONTENT . "/avatars/$oldPhoto");

        $photo = $model->getUserPhoto($props->tokenOwner);

        return $photo;
    }
}
