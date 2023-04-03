<?php

/** http://localhost/stories **/
class Stories extends Api
{
    protected string $apiController = __CLASS__;
    /*
     * Вывод списка всех истрорий
     * GET
     * http://localhost/stories
     */
    protected function GetStoriesAction()
    {
        include_once(PATH . '/Actions/Stories/Get/Stories.php');
        $action = new GetStories();
        return $action->Action($this);
    }
    /* 
     * Вывод конкретной истории
     * GET
     * http://localhost/stories/{id}
     */
    protected function GetStorieAction()
    {
        include_once(PATH . '/Actions/Stories/Get/Storie.php');
        $action = new GetStorie();
        return $action->Action($this);
    }
}
