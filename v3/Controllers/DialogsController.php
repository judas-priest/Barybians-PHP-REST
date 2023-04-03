<?php

/** http://localhost/dialogs **/
class Dialogs extends Api
{
    protected string $apiController = __CLASS__;
    /*
     * Вывод списка всех диалогов
     * GET
     * http://localhost/dialogs
     */
    protected function GetDialogsAction()
    {
        include_once(PATH . '/Actions/Dialogs/Get/Dialogs.php');
        $action = new GetDialogs();
        return $action->Action($this);
    }
    /*
     * Вывод конкретного диалога
     * GET
     * http://localhost/dialogs/{id}
     */
    protected function GetDialogAction()
    {
        include_once(PATH . '/Actions/Dialogs/Get/Dialog.php');
        $action = new GetDialog();
        return $action->Action($this);
    }
}
