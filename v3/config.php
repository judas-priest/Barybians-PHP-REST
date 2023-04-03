<?php
class DotEnv
{
    //https://github.com/devcoder-xyz/php-dotenv
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected $path;


    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    public function load(): void
    {
        if (!is_readable($this->path)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
(new DotEnv('/var/www/_brb.env'))->load();

/*
    Consts
*/
define('API', 3);
define('PATH', "{$_SERVER['DOCUMENT_ROOT']}/v3/");

define('HOST_BRB_PROTOCOL', getenv('HOST_BRB_PROTOCOL'));
define('HOST_BRB_API', getenv('HOST_BRB_API'));
define('HOST_BRB_CONTENT', getenv('HOST_BRB_CONTENT'));

define('DB_BRB_SERVER', getenv('DB_BRB_SERVER'));
define('DB_BRB_USERNAME', getenv('DB_BRB_USERNAME'));
define('DB_BRB_PASSWORD', getenv('DB_BRB_PASSWORD'));
define('DB_BRB_DATABASE', getenv('DB_BRB_DATABASE'));

define('DIR_BRB_CONTENT', getenv('DIR_BRB_CONTENT'));

define('JWT_BRB_KEY', getenv('JWT_BRB_KEY'));

define('AVATARS', HOST_BRB_CONTENT . '/avatars/');
