<?php 
namespace Mita\UranusSocketServer\Configs;

class Config
{
    protected static $settings = [];

    public static function load($file)
    {
        if (file_exists($file)) {
            static::$settings = require $file;
        }
    }

    public static function setConfig(array $config)
    {
        static::$settings = $config;
    }

    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = static::$settings;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
