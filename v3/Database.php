<?php

class Database
{
    protected $mysqli;
    public function __construct()
    {
        try {
            $this->mysqli = new mysqli(DB_BRB_SERVER, DB_BRB_USERNAME, DB_BRB_PASSWORD, DB_BRB_DATABASE);
            if ($this->mysqli->connect_error) {
                throw new Exception('Database connection error', 500);
            }
            $this->mysqli->set_charset('utf8mb4');

            if (TOKEN_OWNER) $this->mysqli->query("UPDATE `users` SET `last_visit` = CURRENT_TIMESTAMP WHERE `users`.`id` = '" . TOKEN_OWNER . "'");
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function select(string $query = '', ?array $params = [], bool $reverse = false)
    {
        try {
            // print_r($query);
            // exit();
            $stmt = $this->executeStatement($query, $params);
            $result = $stmt->get_result()->fetch_row()[0] ?? '';

            $status = ((@strlen($result) > 2) ? true : false); // if '{}' then 404 

            $data = ['json' => $result, 'status' => $status];

            $stmt->close();
            return $data;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    public function select2(string $query = '', ?array $params = [])
    {
        try {
            // print_r($query);
            // exit();
            $stmt = $this->executeStatement($query, $params);
            $result = $stmt->get_result()->fetch_assoc() ?? '';

            $stmt->close();
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    public function update(string $query = '', ?array $params = [])
    {
        try {
            // print_r($query);
            // exit();
            $stmt = $this->executeStatement($query, $params);
            $result = $stmt->get_result() ?? '';

            $stmt->close();
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    public function insert(string $query = '', ?array $params = [])
    {
        try {
            // print_r($query);
            // exit();
            $stmt = $this->executeStatement($query, $params);

            $status = $stmt->affected_rows ?? 0; // ? true : false; //$result['affected_rows']
            $id = $stmt->insert_id ?? 0;

            $data = ['status' => $status, 'id' => $id];

            $stmt->close();
        } catch (Exception $e) {
            $data = ['status' => false];
        }
        return $data;
    }


    private function executeStatement(string $query = '', ?array $params = [])
    {
        try {
            // print_r($query);
            // exit();
            $stmt = $this->mysqli->prepare($query);

            if ($stmt === false) {
                throw new Exception("Unable to do prepared statement: $query");
            }
            /*
            if ($params) {
                $typesArray = [];
                $preparedArray = [];
                foreach ($params as $key => $param) {
                    switch (gettype($param)) {
                        case 'string':
                            $typesArray[] = 's';

                            break;
                        case 'integer':
                            $typesArray[] = 'i';
                            break;
                        default:
                            $typesArray[] = 'NULL';
                            break;
                    }
                    $preparedArray[] = $param;
                }
                $types = implode(',', $typesArray);
                $prepared = implode(',', $preparedArray);

                $stmt->bind_param($types, $prepared);
             }
            print_r($params);
            */
            $stmt->execute($params);

            return $stmt;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    // public function __destruct()
    // {
    //     $this->connection->close();
    // }
}
