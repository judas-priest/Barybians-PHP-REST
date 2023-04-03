<?php

/** http://localhost/posts **/
class Posts extends Api
{
    protected string $apiController = __CLASS__;
    /*****************************************************
     * Posts
     *****************************************************/
    /*
     * Вывод списка всех постов
     * GET
     * http://localhost/posts
     */
    protected function GetPostsAction()
    {
        include_once(PATH . '/Actions/Posts/Get/Posts.php');
        $action = new GetPosts();
        return $action->Action($this);
    }
    /*
     * Получение конкретного поста
     * GET
     * http://localhost/posts/{postId}
     */
    protected function GetPostAction()
    {
        include_once(PATH . '/Actions/Posts/Get/Post.php');
        $action = new GetPost();
        return $action->Action($this);
    }
    /*
     * Оставить пост
     * POST
     * http://localhost/posts
     * @param tokenOwnerId
     * @param title
     * @param text
     */
    protected function SetPostsAction()
    {
        include_once(PATH . '/Actions/Posts/Post/Posts.php');
        $action = new SetPosts();
        return $action->Action($this);
    }
    /*
     * Удалить пост
     * DELETE
     * http://localhost/posts/{postId}
     */
    protected function DelPostAction()
    {
        include_once(PATH . '/Actions/Posts/Delete/Post.php');
        $action = new DelPost();
        return $action->Action($this);
    }
    /*
     * Изменить пост
     * PUT
     * http://localhost/posts/{postId}
     * @param tokenOwnerId
     * @param title
     * @param text
     */
    protected function EditPostAction()
    {
        include_once(PATH . '/Actions/Posts/Put/Post.php');
        $action = new EditPost();
        return $action->Action($this);
    }

    /*****************************************************
     * Comments
     *****************************************************/
    /*
     * Получение комментов конкретного поста
     * GET
     * http://localhost/posts/{postId}/comments
     */
    protected function GetPostCommentsAction()
    {
        include_once(PATH . '/Actions/Posts/Get/Comments.php');
        $action = new GetComments();
        return $action->Action($this);
    }
    /*
     * Получить конкретный коммент поста
     * GET
     * http://localhost/posts/{postId}/comments/{commentId}
     */
    protected function GetPostCommentAction()
    {
        include_once(PATH . '/Actions/Posts/Get/Comment.php');
        $action = new GetComment();
        return $action->Action($this);
    }
    /*
     * Оставить коммент к посту
     * POST
     * http://localhost/posts/{postId}/comments
     * @param tokenOwnerId
     * @param text
     */
    protected function SetPostCommentsAction()
    {
        include_once(PATH . '/Actions/Posts/Post/Comments.php');
        $action = new SetComments();
        return $action->Action($this);
    }
    /*
     * Изменить комментарий к посту
     * PUT
     * http://localhost/posts/{postId}/comments/{commentId}
     * @param tokenOwnerId
     * @param text
     */
    protected function EditPostCommentAction()
    {
        include_once(PATH . '/Actions/Posts/Put/Comments.php');
        $action = new EditComment();
        return $action->Action($this);
    }
    /*
     * Удалить пост
     * DELETE
     * http://localhost/posts/{postId}/comments/{commentId}
     */
    protected function DelPostCommentAction()
    {
        include_once(PATH . '/Actions/Posts/Delete/Comment.php');
        $action = new DelComment();
        return $action->Action($this);
    }
    /*****************************************************
     * Likes
     *****************************************************/
    /*
     * Получение списка лайков конкретного поста
     * GET
     * http://localhost/posts/{postId}/likes
     */
    protected function GetPostLikesAction()
    {
        include_once(PATH . '/Actions/Posts/Get/Likes.php');
        $action = new GetLikes();
        return $action->Action($this);
    }
    /*
     * Поставить лайк конкретному посту
     * POST
     * http://localhost/posts/{postId}/likes
     */
    protected function SetPostLikesAction()
    {
        include_once(PATH . '/Actions/Posts/Post/Likes.php');
        $action = new SetLikes();
        return $action->Action($this);
    }
    /*
     * Снять лайк с конкретного поста
     * DELETE
     * http://localhost/posts/{postId}/likes
     */
    protected function DelPostLikesAction()
    {
        include_once(PATH . '/Actions/Posts/Delete/Likes.php');
        $action = new DelLikes();
        return $action->Action($this);
    }
}
