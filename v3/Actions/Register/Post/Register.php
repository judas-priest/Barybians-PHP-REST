<?php
class SetRegister
{
    public function Action($props)
    {
        require_once(PATH . '/Models/UsersModel.php');

        $firstName = $props->param('firstname');
        $lastName = $props->param('lastname');
        $birthDate = date('Y-m-d', $props->param('birthdate'));
        $sex = $props->param('sex');
        $username = $props->param('username');
        $password = password_hash($props->param('password'), PASSWORD_DEFAULT);
        $photo = 'default.png';

        $model = new UsersModel();

        $checkUsernameExists = $model->authUser($username);
        if ($checkUsernameExists['json']) {
            return [
                'message' => 'username already exists',
                'error' => 500
            ];
        }

        $register = $model->registerUser($firstName, $lastName, $birthDate, $sex, $photo, $username, $password);

        if ($register['status'] && isset($register['id'])) {
            $user = $model->getUsers($register['id']);
            if ($user['status']) {
                return ['json' => "{\"message\":\"Registration was successful\",\"user\":{$user['json']}}", 'status' => $register['status']]; //?
            }
        }
        return [
            'message' => 'an error occurred while registration',
            'error' => 403
        ];
    }
}
