<?php

class LinkPreview extends Api
{
    public string $apiName = 'linkpreview';
    /**
     * Метод GET
     * Вывод списка всех пользователей
     * http://BARYBIANS/users
     * @return string
     */
    public function indexAction()
    {
        throw new RuntimeException($this->requestStatus(400), 400);
    }
    /**
     * Метод GET
     * Вывод по id
     * http://BARYBIANS/users/1
     * @return string
     */
    public function paramAction()
    {
        /*
        $id = explode('/', trim(array_shift($this->params), '/'));
        $id = $id[1];
        if (isset($this->params['start'])) {
            $start = $this->params['start'];
            $end = $this->params['end'] ?? null;
            $messages = $this->GetAllMessages($this->tokenOwner, $id, $start, $end);
            return $this->response($messages, 200);
        }
*/
        //$url = preg_replace('/^.+?=(https?:\/?\/)?/', '', $this->params['url']);
        //print_r($this->params);
        $url = $this->params['url'];
        unset($this->params['r']);
        unset($this->params['url']);
        $url = $url . implode('&', $this->params);

        if (!isset($this->params['full'])) {
            $response = $this->LinkPreview($url);
            if (!isset($response['error'])) {
                return $this->response($response, 200);
            } else {
                return $this->response($response, 500);
            }
        } else {
            $response = $this->LinkPreview($url, 1);
            if (!isset($response['error'])) {
                return $this->response($response, 200);
            } else {
                return $this->response($response, 500);
            }
        }
    }
    public function createAction()
    {
        if (isset($urls)) {
            $urls = json_decode($this->params['urls'], true);
            if (isset($urls[0])) {
                $json = [];
                foreach ($urls as $url) {
                    array_push($json, $this->LinkPreview($url));
                }
                return json_encode($json, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } else {
            return $this->response($this->errors['500'], 500);
        }
        /*if(!isset($response['error'])){
            return $this->response($response, 200);
        }else{
            throw new RuntimeException($this->requestStatus($response['error']), 500);
        }*/
    }
}

class Online extends Api
{
    public string $apiName = 'online';
    /**
     * Метод GET
     * Вывод списка всех пользователей
     * http://BARYBIANS/online
     * @return string
     */
    public function indexAction()
    {
        return $this->response($this->UsersOnline2(), 200);
    }
    /**
     * Метод GET
     * Вывод по id
     * http://BARYBIANS/online?users=*,*
     * @return string
     */
    public function paramAction()
    {

        if (isset($this->params['users'])) {
            $users = $this->params['users'];
            try {
                $user  = $this->UsersOnlineSelective($users);
                return $this->response($user, 200);
            } catch (Error $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
class Dialogs extends Api
{
    public string $apiName = 'dialogs';
    /**
     * Метод GET
     * Вывод списка всех диалогов
     * http://BARYBIANS/dialogs
     * @return string
     */
    public function indexAction()
    {
        $dialogs = $this->Dialogs3($this->tokenOwner);
        if ($dialogs) return $this->response($dialogs, 200);

        return $this->response($this->errors['404m'], 404);
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    public function viewAction()
    {
        /**
         * Метод GET
         * Вывод крайнего сообщения из конкретного диалога
         * http://BARYBIANS/dialogs/{id}
         * @return string
         */
        try {
            $dialog = $this->DialogSpecificUser($this->tokenOwner, $this->uri[0]);
            if ($dialog) return $this->response($dialog, 200);
        } catch (TypeError $e) {
            //echo $e->getMessage();
            throw new RuntimeException($this->requestStatus(400), 400);
        }

        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
//
class Messages extends Api
{
    public string $apiName = 'messages';
    /**
     * Метод GET
     * Вывод всех сообщений от всех пользователей
     * http://BARYBIANS/messages
     * @return string
     */
    public function indexAction()
    {
        $messages = $this->GetAllMessages($this->tokenOwner);
        if ($messages) return $this->response($messages, 200);
        return $this->response($this->errors['404m'], 404);
        /*
        $messages = $this->GetUnreadMessages($this->tokenOwner);
        if ($messages) return $this->response($messages, 200);
        return $this->response($this->errors['404m'], 404);
        */
    }
    /**
     * Метод GET/*
     * Вывод сообщений от конкретного пользователя
     * http://BARYBIANS/messages/{id}
     * @return string
     */
    public function viewAction()
    {
        $id = $this->uri[0] ?? null;
        $id = array_shift($this->uri) ?? null;

        $messages = $this->GetAllMessagesFromDialog($this->tokenOwner, $id);
        if (isset($messages['messages'])) return $this->response($messages, 200);
        $this->UnreadMessages($this->tokenOwner, $id);
        return $this->response($this->errors['404m'], 404);

        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод GET/1
     * http://BARYBIANS/messages/*
     * @return string
     */
    public function paramAction()
    {
        $id = explode('/', trim(array_shift($this->params), '/'));
        $id = $id[1] ?? null;

        if ($id) {
            /**
             * Пагинация сообщений конкретного юзера
             * http://BARYBIANS/messages/{id}?start={startMsgId}&end={lastMsgId}&unread
             */
            $start = (isset($this->params['start']) ? $this->params['start'] : null);
            $end = (isset($this->params['end']) ? $this->params['end'] : null);
            $unread = (isset($this->params['unread']) ? true : false);
            $sum = (isset($this->params['sum']) ? true : false);
            $desc = (isset($this->params['desc']) ? true : false);
            try {
                $messages = $this->GetAllMessagesFromDialog($this->tokenOwner, $id, $unread, $start, $end, $sum, $desc);
                $this->UnreadMessages($this->tokenOwner, $id);
                if (isset($messages['messages'])) return $this->response($messages, 200);
            } catch (TypeError $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(404), 404);
            }
        } else {
            /**
             * Пагинация всех сообщений
             * http://BARYBIANS/messages?start={startMsgId}&end={lastMsgId}&unread
             */
            $start = (isset($this->params['start']) ? $this->params['start'] : null);
            $end = (isset($this->params['end']) ? $this->params['end'] : null);
            $unread = (isset($this->params['unread']) ? true : false);
            try {
                $messages = $this->GetAllMessages($this->tokenOwner, $unread, $start, $end);
                if ($messages) return $this->response($messages, 200);
            } catch (TypeError $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        return $this->response($this->errors['404m'], 404);
    }

    //////////////////////////
    /**
     * Метод GET/1
     * Вывод новых сообщений из переписки Long Poll
     * http://BARYBIANS/messages/*
     * @return string
     *//*
    public function paramAction2()
    {
        $starttime = time();
        require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/func.php';
        $longpoll = true;
        if (isset($this->params['last'], $this->params['userId'])) {
            $messageId = $this->params['last'];
            $id = preg_replace('/[^0-9]/', '', $this->params['userId']);
            do {
                clearstatcache();
                $messages = $this->GetNewMessages2($this->tokenOwner, $id, $messageId);
                $message  = $messages['messages'][0]['id'] ?? 0;
                if ($message > $messageId) {
                    return $this->response($messages, 200);
                    break;
                }
            } while ((time() - $starttime) < 20);
            return $this->response(false, 400);
        }
        if (isset($this->params['last']) && !isset($this->params['userId'])) {
            $messageId = $this->params['last'];
            do {
                clearstatcache();
                $messages = $this->GetUnreadMessages($this->tokenOwner, $messageId);
                $message  = $messages[0]['message']['id'] ?? 0;
                if ($message > $messageId) {
                    return $this->response($messages, 200);
                    break;
                }
            } while ((time() - $starttime) < 20);
            return $this->response(false, 400);
        }
        if (!isset($this->params['last']) && isset($this->params['userId'])) {
            $id = preg_replace('/[^0-9]/', '', $this->params['userId']);
            do {
                clearstatcache();
                $messages = $this->GetNewMessages2($this->tokenOwner, $id);
                $message  = $messages['messages'][0]['id'] ?? null;
                if ($message) {
                    return $this->response($messages, 200);
                    break;
                }
            } while ((time() - $starttime) < 20);
            return $this->response(false, 400);
        }
        if (isset($this->params['state'])) {
            return $this->response($this->ReadStateMessages($this->tokenOwner, $this->params['state']), 200);
        }
        return $this->response(false, 404);
    }
    */
    /**
     * Метод POST
     * Отправить сообщение
     * @return string
     */
    public function createParamAction()
    {
        $id   = $this->uri[0];
        $request = $this->headers['request'] ?? null;
        if (empty($request) || $this->IdempotencyValidator($this->tokenOwner, $request)) throw new RuntimeException($this->requestStatus(412), 412);

        if (isset($id, $this->params['text'])) {
            $message = $this->SendMessage($this->tokenOwner, $id, $this->params['text']);
            if ($message) {
                $this->UnreadMessages($this->tokenOwner, $id);
                return $this->response($message, 200);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
//
class Notifications extends Api
{
    public string $apiName = 'notifications';
    /**
     * Метод GET
     * Вывод списка всех записей
     * http://BARYBIANS/notifications
     * @return string
     */
    public function indexAction()
    {
        $count = $this->NotifyGet($this->tokenOwner);
        if ($count) {
            return $this->response($count, 200);
        }

        return $this->response($this->errors['500'], 500);
    }
    /**
     * Метод GET/1
     * http://BARYBIANS/messages/*
     * @return string
     */
    public function viewAction()
    {
        return $this->response($this->errors['500'], 500);
    }
}
class Posts extends Api
{
    public string $apiName = 'posts';
    /**
     * Метод GET
     * Вывод списка всех постов
     * http://BARYBIANS/posts
     * @return string
     */
    public function indexAction()
    {
        $posts = $this->PostGet(null, 1);
        if ($posts) return $this->response($posts, 200);

        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод GET/1
     * http://BARYBIANS/posts/*
     * @return string
     */
    public function viewAction()
    {
        $postId = array_shift($this->uri);
        try {
            if (isset($postId)) {
                $post = $this->PostGet($postId, 1);
                return $this->response($post, 200);
            }
        } catch (TypeError $e) {
            //echo $e->getMessage();
            throw new RuntimeException($this->requestStatus(400), 400);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    public function paramAction()
    {
        if (isset($this->params['start'])) {
            $start  = $this->params['start'];
            $end  = $this->params['end'] ?? null;
            return $this->response($this->PostPagination(0, $start, $end, 1), 200);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод POST
     * http://BARYBIANS/posts/*
     * @return string
     */
    public function createAction()
    {

        $request = $this->headers['request'] ?? null;
        if (empty($request) || $this->IdempotencyValidator($this->tokenOwner, $request)) throw new RuntimeException($this->requestStatus(412), 412);
        if (isset($this->params['text']) && !empty(trim($this->params['text']))) {
            $title = $this->params['title'] ?? '';
            $text  = $this->params['text'];
            $post  = $this->PostAdd($this->tokenOwner, $title, $text);
            if ($post) {
                $request = $this->PostGet($post, 1);
                return $this->response($request, 200);
            }
        }

        throw new RuntimeException($this->requestStatus(500), 500);
    }
    public function createParamAction()
    {
        $postId = $this->uri[0];
        if ($this->uri[1] === 'like') {
            $this->LikeAdd2($this->tokenOwner, $postId);
            $likes = $this->LikeGet($postId);
            $result = [];
            $result['whoLiked'] = $likes;
            $result['likesCount'] = count($likes);
            return $this->response($result, 200);
        }
    }
    /**
     * Метод PUT
     * http://BARYBIANS/posts/*
     * @return string
     */
    public function updateAction()
    {
        $postId = array_shift($this->uri);
        if (isset($this->params['text'], $postId) && !empty(trim($this->params['text']))) {
            $title = $this->params['title'] ?? '';
            $text  = $this->params['text'];
            try {
                $post = $this->PostEdit($this->tokenOwner, $title, $text, $postId);
                if ($post) {
                    $request = $this->PostGet($postId, 0);
                    return $this->response($request, 200);
                }
            } catch (TypeError $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод DELETE
     * http://BARYBIANS/posts/*
     * @return string
     */
    public function deleteAction()
    {
        $postId = array_shift($this->uri);
        if (isset($postId)) {
            if (isset($this->uri[0])) {
                if ($this->uri[0] === 'like') {
                    $this->LikeDel2($this->tokenOwner, $postId);
                    $likes = $this->LikeGet($postId);
                    $result = [];
                    $result['whoLiked'] = $likes;
                    $result['likesCount'] = count($likes);
                    return $this->response($result, 200);
                }
            } else {
                try {
                    $post = $this->PostDel($this->tokenOwner, $postId);
                    return $this->response($post, 200);
                } catch (TypeError $e) {
                    //echo $e->getMessage();
                    throw new RuntimeException($this->requestStatus(400), 400);
                }
            }
        }

        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
class Comments extends Api
{
    public string $apiName = 'comments';
    /**
     * Метод GET
     * Вывод списка всех записей
     * http://BARYBIANS/comments
     * @return string
     */
    public function indexAction()
    {
        $comments = $this->CommentsGet();
        if ($comments) return $this->response($comments, 200);

        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод GET/1
     * http://BARYBIANS/comments/*
     * @return string
     */
    public function viewAction()
    {
        $commentId = array_shift($this->uri);
        if (isset($commentId)) {
            try {
                $comments = $this->CommentGet2($commentId);
                return $this->response($comments, 200);
            } catch (TypeError $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод POST
     * Создать коммент
     * http://BARYBIANS/comments/*
     * @return string
     */
    public function createAction()
    {
        $request = $this->headers['request'] ?? null;
        if (empty($request) || $this->IdempotencyValidator($this->tokenOwner, $request)) throw new RuntimeException($this->requestStatus(412), 412);
        if (isset($this->params['postId']) && $this->params['text']) {
            try {
                $comment = $this->CommentAdd($this->tokenOwner, $this->params['postId'], $this->params['text']);
                if ($comment !== false) {
                    $request = $this->CommentGet2($comment);
                    return $this->response($request, 200);
                }
                return $this->response($comment, 200);
            } catch (TypeError $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод PUT
     * Изменить коммент
     * http://BARYBIANS/comments/*
     * @return string
     */
    public function updateAction()
    {
        $commentId = array_shift($this->uri);
        if (isset($this->params['text']) && isset($commentId)) {
            $text = $this->params['text'];
            try {
                $comment = $this->CommentEdit($this->tokenOwner, $commentId, $text);
                if ($comment !== false) {
                    $request = $this->CommentGet2($commentId);
                    return $this->response($request, 200);
                }
                return $this->response($comment, 200);
            } catch (TypeError $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод DELETE
     * Удалить коммент
     * http://BARYBIANS/comments/*
     * @return string
     */
    public function deleteAction()
    {
        $commentId = array_shift($this->uri);
        if (isset($commentId)) {
            try {
                $comment = $this->CommentDel2($this->tokenOwner, $commentId);
                return $this->response($comment, 200);
            } catch (TypeError $e) {
                //echo $e->getMessage();
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
//

class Longpoll extends Api
{
    public string $apiName = 'longpoll';
    /**
     * Метод GET
     * Вывод списка всех записей
     * http://BARYBIANS/longpoll
     * @return string
     */
    public function indexAction()
    {
        throw new RuntimeException($this->requestStatus(405), 405);
    }
    /**
     * Метод GET/1
     * http://BARYBIANS/longpoll/messages*
     * @return string
     */
    public function viewAction()
    {
        $starttime = time();
        if ($this->uri[0] === 'messages') {
            if (!isset($this->uri[1])) {
                do {
                    clearstatcache();
                    $messages = $this->GetUnreadMessages($this->tokenOwner);
                    $message  = $messages[0]['messages'][0]['id'] ?? 0;
                    if ($message) {
                        return $this->response($messages, 200);
                        break;
                    }
                } while ((time() - $starttime) < 20);
            } else {
                $id = filter_var($this->uri[1], FILTER_SANITIZE_NUMBER_INT);
                if ($id) {
                    do {
                        clearstatcache();
                        $messages = $this->GetNewMessages($this->tokenOwner, $id);
                        $message  = $messages['messages'][0]['id'] ?? null;
                        if ($message) {
                            return $this->response($messages, 200);
                            break;
                        }
                    } while ((time() - $starttime) < 20);
                }
            }
            return $this->response($this->errors['404m'], 404);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод GET
     * 
     * http://BARYBIANS/longpoll/messages/*?last
     * @return string
     */
    public function paramAction()
    {
        $starttime = time();
        if (str_contains($this->uri[0], 'messages') && isset($this->params['last'])) {
            $messageId = (int) $this->params['last'] ?? null;
            $sender = $this->params['sender'] ?? null;
            if (!isset($this->uri[1])) {
                do {
                    clearstatcache();
                    if (isset($sender)) {
                        $messages = $this->GetUnreadMessages($this->tokenOwner);
                        if ($messages) {
                            $senders = explode(',', $sender);
                            $newMessages = [];
                            foreach ($messages as $key) {
                                if (array_search($key['secondUser']['id'], $senders) === false) {
                                    $newMessages[] = $key;
                                }
                            }
                            if ($newMessages) return $this->response($newMessages, 200);
                        }
                    } else {
                        $messages = $this->GetUnreadMessages($this->tokenOwner, $messageId);
                        if ($messages) return $this->response($messages, 200);
                    }
                } while ((time() - $starttime) < 20);
            } else {
                $id = stristr($this->uri[1], '?', true); //filter_var($this->uri[1], FILTER_SANITIZE_NUMBER_INT);
                if ($id) {
                    do {
                        clearstatcache();
                        $messages = $this->GetNewMessages($this->tokenOwner, $id, $messageId);
                        $message  = $messages['messages'][0]['id'] ?? null;
                        if ($message > $messageId) {
                            return $this->response($messages, 200);
                            break;
                        }
                    } while ((time() - $starttime) < 20);
                }
            }
            return $this->response($this->errors['404m'], 404);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод PUT
     * Изменить коммент
     * http://BARYBIANS/longpoll/*
     * @return string
     */
    public function updateAction()
    {
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод DELETE
     * Удалить коммент
     * http://BARYBIANS/longpoll/*
     * @return string
     */
    public function deleteAction()
    {
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
//

class Account extends Api
{
    public string $apiName = 'account';
    /**
     * Метод GET/1
     * Вывод своего статуса
     * http://BARYBIANS/account/status
     * @return string
     */
    public function viewAction()
    {
        $method = array_shift($this->uri);
        switch ($method) {
            case 'status':
                $status = $this->StatusGet($this->tokenOwner);
                if ($status) return $this->response($status, 200);
                break;
            case 'photo':
                $photo = $this->PhotoGet($this->tokenOwner);
                if ($photo) return $this->response($photo, 200);
                break;
        }

        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод POST
     * Замена фотографии профиля
     * http://BARYBIANS/account/photo
     * @return string
     */
    public function createParamAction()
    {
        if ($this->uri[0] === 'photo') {
            $request = $this->headers['request'] ?? null;
            if (empty($request) || $this->IdempotencyValidator($this->tokenOwner, $request)) throw new RuntimeException($this->requestStatus(412), 412);
            if (isset($this->files['photo'])) {
                $photo = $this->PhotoSet($this->tokenOwner, $this->files['photo']);
                if ($photo) return $this->response($photo, 200);
            }
        }

        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод PUT
     * Изменить статус
     * http://BARYBIANS/account/status
     * @return string
     */
    public function updateAction()
    {
        if (isset($this->params['text'])) {
            $text   = $this->params['text'];
            $status = $this->StatusSet($this->tokenOwner, $text);
            return $this->response($status, 200);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод DELETE
     * Удалить статус
     * http://BARYBIANS/account/status
     * @return string
     */
    public function deleteAction()
    {
        if (isset($this->uri[0])) {
            if ($this->uri[0] === 'status') {
                $text   = '';
                $status = $this->StatusSet($this->tokenOwner, $text);
                return $this->response($status, 200);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
class Stickers extends Api
{
    public string $apiName = 'stickers';
    /**
     * Метод GET
     * Вывод списка всех записей
     * http://BARYBIANS/stickers
     * @return string
     */
    public function indexAction()
    {
        return $this->response($this->StickersGet($this->tokenOwner), 200);
    }
}
//
class Stories extends Api
{
    public string $apiName = 'stories';
    /**
     * Метод GET
     * Авторизация
     * http://BARYBIANS/stories
     * @return string
     */
    public function indexAction()
    {
        return $this->response($this->StoriesGet(), 200);
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    /**
     * Метод GET
     * Авторизация
     * http://BARYBIANS/stories
     * @return string
     */
    public function viewAction()
    {
        $id = array_shift($this->uri);
        try {
            return $this->response($this->StoriesGet($id), 200);
        } catch (TypeError $e) {
            throw new RuntimeException($this->requestStatus(400), 400);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
//
//
class Auth extends Api
{
    public string $apiName = 'auth';
    /**
     * Метод POST
     * Авторизация
     * http://BARYBIANS/auth
     * @return string
     */
    public function createAction()
    {
        if (isset($this->params['username'], $this->params['password'])) {
            $login = $this->Login($this->params['username'], $this->params['password']);
            $code = $login['code'];
            unset($login['code']);
            return $this->response($login, $code);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
//
class Register extends Api
{
    public string $apiName = 'register';
    /**
     * Метод POST
     * Регистрация
     * http://BARYBIANS/register
     * @return string
     */
    public function createAction()
    {
        if (isset($this->params['firstName']) && isset($this->params['lastName']) && isset($this->params['birthDate']) && isset($this->params['sex']) && isset($this->params['username']) && isset($this->params['password'])) {
            $signIn = $this->Signin($this->params['firstName'], $this->params['lastName'], $this->params['birthDate'], $this->files['photo'], $this->params['sex'], $this->params['username'], $this->params['password']);
            if (isset($signIn['error'])) {
                return $this->response($signIn['error'], 500);
            } else {
                unset($this->params['r']);
                unset($this->params['password']);
                return $this->response(['message' => 'Registration was successful!', 'user' => $this->params], 200);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}

class Boiler extends Api
{
    public string $apiName = 'boiler';
    public function indexAction()
    {
        $boiler = $this->boilerGet();
        if ($boiler) return $this->response($boiler, 200);
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    public function createAction()
    {
        if (isset($this->params['name'], $this->files['mp3'])) {
            if (!strripos($this->files['mp3']['name'], 'mp3') || $this->files['mp3']['size'] > 1048576) throw new RuntimeException($this->requestStatus(400), 400);
            $boiler = $this->boilerSet($this->params['name'], $this->files['mp3']);
            if (isset($boiler)) return $this->response(['name' => $this->params['name'], 'mp3' => $boiler], 200);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    public function updateAction()
    {
        $mp3 = array_shift($this->uri);
        if (isset($this->params['name'], $mp3)) {
            $boiler = $this->boilerUpdate($this->params['name'], $mp3);
            if ($boiler) return $this->response(['name' => $this->params['name'], 'mp3' => $mp3], 200);
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
    public function deleteAction()
    {
        $mp3 = array_shift($this->uri);
        if (isset($mp3)) {
            try {
                $boiler = $this->boilerDelete($mp3);
                if ($boiler) return $this->response($boiler, 200);
                throw new RuntimeException($this->requestStatus(404), 404);
            } catch (TypeError $e) {
                throw new RuntimeException($this->requestStatus(400), 400);
            }
        }
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
class Token extends Api
{
    public string $apiName = 'token';
    public function indexAction()
    {
        if ($this->tokenOwner) return $this->response(['id' => $this->tokenOwner], 200);
        throw new RuntimeException($this->requestStatus(500), 500);
    }
}
class SSE extends Api
{
    public string $apiName = 'sse';
    public function paramAction()
    {
        Header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $start = $this->params['start'];

        //$lastEventId = floatval(isset($_SERVER["HTTP_LAST_EVENT_ID"]) ? $_SERVER["HTTP_LAST_EVENT_ID"] : 0);
        //if ($lastEventId == 0) $lastEventId = floatval(isset($_GET["lastEventId"]) ? $_GET["lastEventId"] : 0);

        echo ":" . str_repeat(" ", 24) . "\n"; // 2 kB padding for IE
        echo "retry: 20\n";

        /* event-stream */
        //$i = $lastEventId;
        //$c = $i + 100;

        while (true) {
            $messages = $this->GetNewMessages2($this->tokenOwner, 21, $start);
            $message  = $messages['messages'][0]['id'] ?? null;
            if ($message) {
                //echo "id: " . $i . "\n";
                echo "data: " . json_encode($messages) . "\n\n";
                $start = $messages['messages'][array_key_last($messages['messages'])]['id'];
                ob_flush();
                flush();
                //break;
            }
        }

        /*
        while (++$i < $c) {
            $messages = $this->GetAllMessagesFromDialog(1, 21, 0);
            echo "id: " . $i . "\n";
            echo "data: " . json_encode($messages) . ";\n\n";
            ob_flush();
            flush();
            sleep(1);
        }*/
    }
}
