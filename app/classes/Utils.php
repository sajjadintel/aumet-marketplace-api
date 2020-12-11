<?php
class Utils
{

    public static function getClientIP()
    {

        if (getenv('ENV') == Constants::ENV_LOCAL) {
            $ip = getenv('LOCAL_IP');
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function getRootDir()
    {
        return dirname(dirname(dirname(__FILE__))) . '/';
    }

    public static function encryptMessage($string)
    {
        $secret_key = getenv('ENCRYPT_KEY');
        $encrypt_method = "AES-256-CBC";
        $iv = substr($secret_key, 0, 16);

        return openssl_encrypt($string, $encrypt_method, $secret_key, 0, $iv);
    }

    public static function decryptMessage($string)
    {
        $secret_key = getenv('ENCRYPT_KEY');
        $encrypt_method = "AES-256-CBC";
        $iv = substr($secret_key, 0, 16);

        return openssl_decrypt($string, $encrypt_method, $secret_key);
    }

    public static function arrayKeysToLowercase($array)
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            $newArray[strtolower($key)] = $value;
        }
        return $newArray;
    }
    function getRootDirectory()
    {
        return dirname(dirname(dirname(__FILE__))) . '/';
    }

    function getTempDirectory()
    {
        return dirname(dirname(dirname(__FILE__))) . '/tmp/';
    }

    function getCurrentDirectory()
    {
        return __DIR__;
    }

    function downloadFile($filePath)
    {
        header('Content-Type: ' . mime_content_type($filePath));
        $buffer = '';
        $cnt = 0;
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return false;
        }

        while (!feof($handle)) {
            $buffer = fread($handle, CHUNK_SIZE);
            echo $buffer;
            ob_flush();
            flush();
        }

        fclose($handle);
    }
    static function isValidMd5($md5 = '')
    {
        return preg_match('/^[a-f0-9]{32}$/', $md5);
    }
    static function stringInEnvArray($string, $envString)
    {
        $envArray = explode(",", $envString);
        return in_array($string, $envArray);
    }
}
