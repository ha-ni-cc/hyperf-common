<?php


namespace App\Util;

use PragmaRX\Google2FA\Google2FA;

class Google2FaUtil
{
    public static function generate($name, $id, $length = 32, $prefix = '')
    {
        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey($length, $prefix);
        $holder = $id . '@' . time();
        $qrcUrl = $google2fa->getQRCodeUrl($name, $holder, $secretKey);
        return ['secret_key' => $secretKey, 'qrc_url' => $qrcUrl];
    }

    public static function verify($key, $code, $ts = 0)
    {
        $google2fa = new Google2FA();
        $ts = $google2fa->verifyKeyNewer($key, $code, $ts);
        if ($ts !== false) {
            return $ts;
        } else return false;
    }
}