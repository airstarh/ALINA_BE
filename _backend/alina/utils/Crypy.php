<?php

namespace alina\utils;

class Crypy
{
    ##################################################
    public $ENCRYPTION_KEY;

    public function __construct()
    {
        $k                    = [
            Request::obj()->IP,
            Request::obj()->BROWSER,
        ];
        $this->ENCRYPTION_KEY = implode('', $k);
    }
    ##################################################
    #region Reversible
    public function revencr($plaintext, $ENCRYPTION_KEY = NULL)
    {
        if (empty($ENCRYPTION_KEY)) {
            $ENCRYPTION_KEY = $this->ENCRYPTION_KEY;
        }
        $ivlen          = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv             = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $hmac           = hash_hmac('sha256', $ciphertext_raw, $ENCRYPTION_KEY, $as_binary = TRUE);
        $ciphertext     = base64_encode($iv . $hmac . $ciphertext_raw);

        return $ciphertext;
    }

    public function revdecr($ciphertext, $ENCRYPTION_KEY = NULL)
    {
        if (empty($ENCRYPTION_KEY)) {
            $ENCRYPTION_KEY = $this->ENCRYPTION_KEY;
        }
        $c              = base64_decode($ciphertext);
        $ivlen          = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv             = substr($c, 0, $ivlen);
        $hmac           = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $plaintext      = openssl_decrypt($ciphertext_raw, $cipher, $ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac        = hash_hmac('sha256', $ciphertext_raw, $ENCRYPTION_KEY, $as_binary = TRUE);
        if (hash_equals($hmac, $calcmac)) {
            return $plaintext;
        }

        return FALSE;
    }
    #endregion Reversible
    ##################################################
}
