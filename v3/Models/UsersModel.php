<?php
class UsersModel extends Database
{
    // Users JSON grouping by MySQL
    public function UsersJson(string $prefix = '')
    {
        return
            "JSON_OBJECT(
            'userId',{$prefix}userId,
            'firstName',{$prefix}firstName,
            'lastName',{$prefix}lastName,
            'photo',CONCAT('" . AVATARS . "',{$prefix}photo),
            'photo256',CONCAT('" . AVATARS . "mid/',{$prefix}photo),
            'photo128',CONCAT('" . AVATARS . "min/',{$prefix}photo),
            'status',{$prefix}status,
            'birthDate',{$prefix}birthDate,
            'sex',{$prefix}sex,
            'roleId',{$prefix}role,
            'lastVisit',{$prefix}lastVisit,
            'postsCount',{$prefix}postsCount
            )";
    }
    // Users table query
    public function UsersTable(string $users = 'users', string $prefix = '')
    {
        return
            "`{$users}`.`id` as {$prefix}userId,
            `{$users}`.`name` AS {$prefix}firstName,
            `{$users}`.`surname` AS {$prefix}lastName,
            `{$users}`.`photo` as {$prefix}photo,
            `{$users}`.`status` as {$prefix}status,
            UNIX_TIMESTAMP(`{$users}`.`birth_date`) AS {$prefix}birthDate,
            IF(`{$users}`.`sex`,'female','male') as {$prefix}sex,
            `{$users}`.`role` AS {$prefix}role,
            UNIX_TIMESTAMP(`{$users}`.`last_visit`) AS {$prefix}lastVisit,
            COUNT(`{$prefix}p`.`id`) as {$prefix}postsCount";
    }
    // Posts query
    public function UsersPostsTable(string $users = 'users', string $prefix = '')
    {
        return "LEFT JOIN posts as {$prefix}p ON {$users}.id = {$prefix}p.user_id";
    }



    public function getUsers(int $userId = 0, int $yourId = 0, bool $online = false, int $offlineTime = 180)
    {
        $groupBy = '';

        if ($userId) {
            $where  =  $userId ? "WHERE users.id = '$userId'" : '';
            $groupBy = 'GROUP BY userId';
        } else  $where = $online ? "WHERE (UNIX_TIMESTAMP(CURRENT_TIMESTAMP) - UNIX_TIMESTAMP(users.last_visit)) <= $offlineTime" : "WHERE users.id != $yourId";


        $array  = !$userId ? 'JSON_ARRAYAGG' : '';


        return $this->select(
            "SELECT DISTINCT
            $array({$this->UsersJson()})
            from (
                SELECT DISTINCT {$this->UsersTable()}
                FROM users
                {$this->UsersPostsTable()}
                $where
                GROUP BY userId ORDER BY userId)
            as user
            $groupBy;"
        );
    }
    public function getUserStatus(int $userId)
    {
        return $this->select("SELECT DISTINCT JSON_OBJECT('status',status) FROM users WHERE id = '$userId';");
    }
    public function setUserStatus(int $userId, string $status)
    {
        return $this->update("UPDATE `users` SET `status` = ? WHERE `users`.`id` = '$userId';", [$status]);
    }
    public function getUserPhoto(int $userId, bool $url = true)
    {
        if ($url) $photo = "CONCAT('" . AVATARS . "',photo)";
        else $photo = 'photo';

        return $this->select("SELECT DISTINCT JSON_OBJECT('photo',$photo) FROM users WHERE id = $userId;");
    }
    public function setUserPhoto(int $userId, string $photoNewName)
    {
        return $this->update("UPDATE `users` SET `photo` = ? WHERE `users`.`id` = $userId", [$photoNewName]);
    }
    public function authCheckUsername(string $username)
    {
        return $this->select2(
            "SELECT DISTINCT users.password
             FROM users
             WHERE users.login = '$username';"
        );
    }
    public function authUser(string $username)
    {
        return $this->select(
            "SELECT DISTINCT
{$this->UsersJson()}
from (
    SELECT DISTINCT {$this->UsersTable()}
    FROM users
    {$this->UsersPostsTable()}
    WHERE users.login = '$username'
    GROUP BY userId ORDER BY userId)
as user;"
        );
    }
    public function registerUser(string $firstName, string $lastName, string $birthDate, bool $sex, string $photo, string $username, string $password, string $status = 'Я - барыбинец', int $role = 0)
    {
        return $this->insert(
            'INSERT INTO `users` (`name`,`surname`,`birth_date`,`sex`,`photo`,`login`,`password`,`status`,`role`) VALUES(?,?,?,?,?,?,?,?,?);',
            [$firstName, $lastName, $birthDate, $sex, $photo, $username, $password, $status, $role]
        );
    }
}
