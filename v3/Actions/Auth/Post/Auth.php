<?php
class SetAuth
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');
        $username = $props->param('username');
        $password = $props->param('password');

        $model = new UsersModel();

        $checkUsernameExists = $model->authCheckUsername($username);

        if (isset($checkUsernameExists['password']) && @password_verify($password, $checkUsernameExists['password'])) {
            $user = $model->authUser($username);


            $token = [
                'iss' => 'Barybians',
                'aud' => json_decode($user['json'], true)['userId'],
                'iat' => TIMESTAMP,
                //'nbf' => TIMESTAMP + 2,
                'exp' => TIMESTAMP + (60 * 60 * 24 * 90) //3 month
            ];
            include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/BeforeValidException.php';
            include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/ExpiredException.php';
            include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/SignatureInvalidException.php';
            include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/JWT.php';
            $jwt = JWT::encode($token, getenv('JWT_BRB_KEY'));


            $response['status'] = 200;
            $response['json']['user'] = $user;
            $response['json']['token'] = $jwt;

            return ['json' => "{\"user\":{$user['json']},\"token\":\"{$jwt}\"}", 'status' => $user['status']]; //?
        }

        return [
            'message' => 'access denied',
            'error' => 403
        ];
    }
}
