<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(60);
require_once 'config.php';
require_once 'Database.php';

abstract class Api
{
    protected string $apiController = ''; //Имя контроллера
    protected string $method = '';        //GET|POST|PUT|DELETE
    public array $uri = [];               //Форматированные URI
    public array $headers;                //Форматированные заголовки
    protected string $action = '';        //Название метод для выполнения
    public array $errors    = [];         //Массив кодов ошибок и сообщений
    public int $tokenOwner;               //id на соновании токена
    public function __construct()
    {
        //header('Content-Encoding: gzip'); //включение gzip
        header('Content-Type: application/json');
        header_remove('Set-Cookie');
        //Массивы query, body параметров, файлов и заголовков 
        $temp_request_uri = ucwords(trim($_SERVER['REQUEST_URI'], '/'), '/');
        $this->uri = explode('/',  $temp_request_uri);

        $this->headers = array_change_key_case(apache_request_headers());

        //Определение метода запроса
        $this->method = $_SERVER['REQUEST_METHOD'];

        if ($this->method === 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            switch ($_SERVER['HTTP_X_HTTP_METHOD']) {
                case 'DELETE':
                    $this->method = 'DELETE';
                case 'PUT':
                    $this->method = 'PUT';
                default:
                    throw new Exception('Unexpected Header');
            }
        }
    }
    private function jwtValidator()
    {
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/BeforeValidException.php';
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/ExpiredException.php';
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/SignatureInvalidException.php';
        include_once $_SERVER['DOCUMENT_ROOT'] . '/libs/php-jwt-master/src/JWT.php';
        // декодирование JWT
        if (isset($this->headers['authorization'])) {
            $parameter = explode(' ', $this->headers['authorization']);
            $jwt       = $parameter[1] ?? null;

            // если JWT не пуст
            if ($jwt) {
                try {
                    $decoded              = JWT::decode($jwt, getenv('JWT_BRB_KEY'), ['HS256']);
                    $decoded              = json_decode(json_encode($decoded), true);
                    $json['verification'] = true;
                    $json['owner']        = $decoded['aud'];

                    return $json;
                }
                // ошибка декодирования
                catch (Exception $e) {
                    exit($this->error(0, 401));
                }
            }
        }
        exit($this->error(0, 401));
    }
    protected function response(array $response, bool $empty = true)
    {
        //Error handling
        if (isset($response['error'])) return $this->error($response['message'], $response['error']);

        //If $empty == true than returns empty object
        //Else returns error message with 404 http code
        if ($response['status'] || $empty) {
            header("HTTP/1.1 200 OK");
            /* if response is false then returns emprty object */
            return (isset($response['json']) && $response['json']) ? $response['json'] : '{}'; //gzencode
        } else {
            return $this->error('not found', 404);
        }
    }
    protected function error(string $errorMessage = '', int $errorCode = 500)
    {
        header("HTTP/1.1 $errorCode " . $this->requestStatus($errorCode));
        $message = $errorMessage ? $errorMessage : $this->requestStatus($errorCode);
        $json = json_encode(['error' => $errorCode, 'message' => $message]);

        return ($json); //gzencode
    }
    public function requestStatus(int $code)
    {
        $status = [
            0 => '',
            200 => 'OK',
            204 => 'No Content',
            302 => 'Moved Temporarily',
            400 => 'Bad Request',
            401 => 'Token is invalid or missing',
            403 => 'Access denided',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            412 => 'Idempotency key is duplicate or missing',
            500 => 'Internal Server Error'
        ];
        return ($status[$code]) ? $status[$code] : $status[500];
    }
    protected function getAction()
    {
        switch ($this->method) {
            case 'GET':
                $action = 'Get';
                break;
            case 'POST':
                $action = 'Set';
                break;
            case 'PUT':
                $action = 'Edit';
                break;
            case 'DELETE':
                $action = 'Del';
                break;
            default:
                return $this->error(0, 401);
        }
        if (count($this->uri) === 1) {
            if (preg_replace('~\d~', '', $this->uri[0]) === '') {
                return $action . substr($this->apiController, 0, -1) . 'Action';
            } else return "$action{$this->uri[0]}Action";
        } else if (count($this->uri) > 1) {
            $full_name_method = '';
            $full_name_method = str_replace('/', '', preg_replace('~./[0-9]+~', '',  parse_url($_SERVER['REQUEST_URI'])['path']));
            return "$action{$full_name_method}Action";
        } else return "$action{$this->apiController}Action";
    }
    /*
    * Filtering and sorting GET parameters, POST body and files
    */
    public function param(string $param)
    {
        if ($this->method !== 'PUT' && $this->method !== 'DELETE') {
            $tempParams = array_change_key_case($_REQUEST);
        } else {
            parse_str(file_get_contents('php://input'), $data);
            $tempParams = array_change_key_case($data);
        }

        $tempParams = array_merge($tempParams, $_FILES);


        // var_dump($tempParams);
        // exit();
        $strings  = ['text', 'title', 'username', 'password', 'firstname', 'lastname', 'sex'];
        $integers = ['offset', 'limit', 'birthdate'];
        $booleans = ['desc', 'unread', 'online'];
        $binary   = ['image'];

        $min_strings = ['text' => 1,    'title' => 0,  'username' => 4,  'password' => 4,  'firstname' => 2,  'lastname' => 2,  'sex' => 1];
        $max_strings = ['text' => 5000, 'title' => 50, 'username' => 20, 'password' => 50, 'firstname' => 20, 'lastname' => 20, 'sex' => 6];


        foreach ($tempParams as $key => $value) {
            if ($key == $param) {
                if (in_array($key, $strings)) {
                    $preparedValue = (string) trim($value);
                    if (mb_strlen($preparedValue) < $min_strings[$key]) exit($this->error("$key must be at least {$min_strings[$key]} characters", 400));
                    if (mb_strlen($preparedValue) > $max_strings[$key]) $preparedValue = mb_strimwidth($preparedValue, 0, $max_strings[$key], '...'); //?

                    if ($key === 'sex') $preparedValue = (($preparedValue == 0 || $preparedValue === 'male') ? 0 : 1);
                    return $preparedValue;
                } elseif (in_array($key, $integers)) {
                    $preparedValue = (int) $value;
                    return $preparedValue;
                } elseif (in_array($key, $booleans)) {
                    if ($key === 'online') $value = ($value === '' ? true : $value);
                    return filter_var($value, FILTER_VALIDATE_BOOL);
                } elseif (in_array($key, $binary)) {
                    if (!$value['error']) return $value;
                } else {
                    return (string) trim($value);
                }
            }
        }

        switch ($param) {
            case 'text':
                exit($this->error('text must exist and be an string', 400));
                break;
            case 'title':
                return '';
                break;
            case 'username':
                exit($this->error('username must exist and be an string', 400));
                break;
            case 'password':
                exit($this->error('password must exist and be an string', 400));
                break;
            case 'firstname':
                exit($this->error('firstname must exist and be an string', 400));
                break;
            case 'lastname':
                exit($this->error('lastname must exist and be an string', 400));
                break;
            case 'sex':
                exit($this->error('sex must exist and be an string', 400));
                break;
            case 'offset':
                exit($this->error('offset must exist and be an integer', 400));
                break;
            case 'limit':
                return null;
                break;
            case 'birthdate':
                break;
            case 'desc':
                return true;
                break;
            case 'unread':
                return false;
                break;
            case 'online':
                return false;
                break;
            case 'image':
                exit($this->error('image must exist and be an image', 400));
                break;
            default:
                exit($this->error('params error', 500));
                break;
        }

        /*
        foreach ($tempParams as $key => $value) {
            if ($key === 'r') continue;
            if (in_array($key, $strings)) {
                $preparedValue = (string) trim($value);
                if (mb_strlen($preparedValue) < $min_strings[$key]) exit($this->error("$key must be at least {$min_strings[$key]} characters", 400));
                if (mb_strlen($preparedValue) > $max_strings[$key]) $preparedValue = mb_strimwidth($preparedValue, 0, $max_strings[$key], '...'); //?
                $this->params[$key] =  $preparedValue;
            } elseif (in_array($key, $integers)) {
                $preparedValue = (int) $value;
                $this->params[$key] =  $preparedValue;
                //exit(var_dump($preparedValue));
            } elseif (in_array($key, $booleans)) $this->params[$key] = filter_var($value, FILTER_VALIDATE_BOOL);
            else $this->params[$key] = (string) trim($value);
        }
        */
    }
    public function run()
    {
        //Сдвиг указателя URI
        array_shift($this->uri);
        define('TIMESTAMP', time());
        /*
         * JWT
         */
        if ($this->apiController !== 'Auth' && $this->apiController !== 'Register') {
            $jwt = $this->jwtValidator();
            //Если токен валидный
            $validState = $jwt['verification'] ?? null;
            //АйДи владельца токена
            $this->tokenOwner = (int) $jwt['owner'] ?? null;
            define('TOKEN_OWNER', $this->tokenOwner);
            /*
             * uuid4 idempotency validation
             */
            if ($this->method === 'POST') {
                $request = $this->headers['request'] ?? '';
                if (mb_strlen($request) < 36 || mb_strlen($request) > 36) exit($this->error('', 412));
                require_once(PATH . '/Methods/IdempotencyValidator.php');
                $uuid = new IdempotencyValidator();
                $validator = $uuid->IdempotencyValidator($this->tokenOwner, $request);
                if (!$validator) exit($this->error('', 412));
            }
            /* Если передан заголовок невидимки */
            //!!!! !isset($this->headers['invisibleheaderexist']) ? $this->LastVisit($this->tokenOwner) : '';
        } else {
            $validState = true;
            define('TOKEN_OWNER', false); //костыль для базы данных
        }
        //Определение действия для обработки
        if ($validState) {
            $this->action = $this->getAction();
            // print_r(parse_url($_SERVER['REQUEST_URI'])['path']);
            // print_r($this->action);
            // exit();

            //Если метод определен в контроллере
            if (method_exists($this, $this->action)) {
                $response = $this->{$this->action}();
                return $this->response($response);
            } else {
                return $this->error(0, 405); //Method Not Allowed
            }
        }
    }
}
