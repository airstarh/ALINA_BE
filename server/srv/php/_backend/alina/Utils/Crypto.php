<?php

namespace alina\Utils;

class Crypy
{
    ##################################################
    public function __construct()
    {
    }
    ##################################################
    #region Reversible
    protected function getKey()
    {
        $k              = [
            Request::obj()->IP,
            Request::obj()->BROWSER,
        ];
        $ENCRYPTION_KEY = implode('', $k);

        return $ENCRYPTION_KEY;
    }

    public function encrypt($plaintext, $ENCRYPTION_KEY = null)
    {
        if (empty($ENCRYPTION_KEY)) {
            $ENCRYPTION_KEY = $this->getKey();
        }
        $ivlen          = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv             = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $hmac           = hash_hmac('sha256', $ciphertext_raw, $ENCRYPTION_KEY, $as_binary = true);
        $ciphertext     = base64_encode($iv . $hmac . $ciphertext_raw);

        return $ciphertext;
    }

    public function decrypt($ciphertext, $ENCRYPTION_KEY = null)
    {
        if (empty($ENCRYPTION_KEY)) {
            $ENCRYPTION_KEY = $this->getKey();
        }
        $c              = base64_decode($ciphertext);
        $ivlen          = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv             = substr($c, 0, $ivlen);
        $hmac           = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $plaintext      = openssl_decrypt($ciphertext_raw, $cipher, $ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac        = hash_hmac('sha256', $ciphertext_raw, $ENCRYPTION_KEY, $as_binary = true);
        if (hash_equals($hmac, $calcmac)) {
            return $plaintext;
        }

        return false;
    }
    #endregion Reversible
    ##################################################
}
