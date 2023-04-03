<?php

class GetStories
{
    public function Action($props)
    {
        require_once(PATH . '/Models/StoriesModel.php');
        $offset = $props->param('offset');
        $desc = $props->param('desc');

        $model = new StoriesModel($props->tokenOwner);
        $stories = $model->getStories(0, $offset, 25, false, $desc);

        return $stories;
    }
}
