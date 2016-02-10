<?php
// This is global bootstrap for autoloading

class PsfConfig
{
    private static $apiUrl;
    private static $apiHostname;
    private static $apiUsername;
    private static $apiPassword;
    private static $primaryMx;

    public static function load()
    {
        self::$apiHostname = gethostname();
        self::$apiUrl = 'http://'.self::$apiHostname;

        $conf = parse_ini_file('/etc/spamexperts/spampanel.conf', true);

        self::$apiUsername = $conf['production']['api.username'];
        self::$apiPassword = $conf['production']['api.password'];
        self::$primaryMx = 'mx.'.preg_replace('/^server\d+\.(.*)/i', '$1', gethostname());
    }

    public static function getApiUrl()
    {
        return self::$apiUrl;
    }

    public static function getApiHostname()
    {
        return self::$apiHostname;
    }

    public static function getApiUsername()
    {
        return self::$apiUsername;
    }

    public static function getApiPassword()
    {
        return self::$apiPassword;
    }

    public static function getPrimaryMX()
    {
        return self::$primaryMx;
    }
}

PsfConfig::load();