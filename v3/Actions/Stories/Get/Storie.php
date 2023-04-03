<?php

class GetStorie
{
    public function Action($props)
    {
        require_once(PATH . '/Models/StoriesModel.php');
        $storieId = (int) $props->uri[0];
        $model = new StoriesModel($props->tokenOwner);
        $storie = $model->getStories($storieId);

        return $storie;
    }
}
