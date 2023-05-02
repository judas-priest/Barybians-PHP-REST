<?php
class IdempotencyValidator extends Database
{
    public function IdempotencyValidator(int $id, ?string $uuid = null)
    {
        if (!$uuid) return false;
        $response =  $this->select2("SELECT `requests`.`request` FROM `requests` WHERE `requests`.`user_id` = '$id' ORDER BY id DESC LIMIT 1;");

        if (isset($response['request']) && $response['request'] === $uuid) return false;

        $this->insert(
            'INSERT INTO `requests` (`id`,`user_id`,`request`) VALUES (NULL,?,?) ON DUPLICATE KEY UPDATE `request`=?;',
            [$id, $uuid, $uuid]
        );
        return true;
    }
}
