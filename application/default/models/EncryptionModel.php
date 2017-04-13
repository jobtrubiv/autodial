<?php

class EncryptionModel
{
    const ENCRYPTION_TYPE_SHA1 = 'sha1';
    const ENCRYPTION_TYPE_MD5 = 'md5';
    const ENCRYPTION_TYPE_RSA = 'rsa';
    const ENCRYPTION_TYPE_MCRYPT = 'mcrypt';
    const ENCRYPTION_TYPE_REALM = 'asterisk';

    protected static $_instance = null;
    protected static $encryptionType = '';

    /**************************************************************************************************
     * Set settings
     *************************************************************************************************/

    private static function setEncryptionType($encryptionType)
    {
        self::$encryptionType = $encryptionType;
    }

    /**************************************************************************************************
     * Public functions
     *************************************************************************************************/

    /**
     * Зашифровать пароль для Asterisk
     *
     * @param string $userName
     * @param string $password
     * @return string
     */
    public function asteriskMd5($userName, $password)
    {
        //md5('4732030036-101:asterisk:ajYEwKTJXH')
        $data = $userName . ':' . self::ENCRYPTION_TYPE_REALM . ':' . $password;

        return md5($data);
    }

    /**
     * Зашифровать текст
     *
     * @param string $data
     * @return string
     */
    public function encrypt($data)
    {
        $encrypted = '';
        switch (self::$encryptionType)
        {
            case self::ENCRYPTION_TYPE_SHA1:{
                $encrypted = sha1($data);
                break;
            }
            case self::ENCRYPTION_TYPE_MD5:{
                $encrypted = md5($data);
                break;
            }
            case self::ENCRYPTION_TYPE_RSA:{
                $publicKey = file_get_contents(self::$publicFile);

                //$pk = openssl_get_publickey($publicKey);
                openssl_public_encrypt($data, $encrypt, $publicKey, OPENSSL_PKCS1_PADDING);

                $encrypted = chunk_split(base64_encode($encrypt));
                break;
            }
            case self::ENCRYPTION_TYPE_MCRYPT:{

                $encrypted = $this->mcryptEncrypt($data);

                break;
            }
        }

        return $encrypted;
    }

    /**
     * @param string $data
     * @return string
     */
    public function decrypt($data)
    {
        $decrypted = '';
        switch (self::$encryptionType)
        {
            case self::ENCRYPTION_TYPE_SHA1:{
                $decrypted = '';
                break;
            }
            case self::ENCRYPTION_TYPE_MD5:{
                $decrypted = '';
                break;
            }
            case self::ENCRYPTION_TYPE_RSA:{
                $privateKey = file_get_contents(self::$privateFile);

                $pk  = openssl_get_privatekey($privateKey);

                openssl_private_decrypt(base64_decode($data), $out, $pk);

                $decrypted = $out;
                break;
            }
        }

        return $decrypted;
    }

    public function generatePassword($length = 10)
    {
        $arr = array(
            'a','b','c','d','e','f',
            'g','h','i','j','k','l',
            'm','n','o','p','r','s',
            't','u','v','x','y','z',
            'A','B','C','D','E','F',
            'G','H','I','J','K','L',
            'M','N','O','P','R','S',
            'T','U','V','X','Y','Z',
            '1','2','3','4','5','6',
            '7','8','9','0');

        $pass = "";
        for($i = 0; $i < $length; $i++){
            $index = rand(0, count($arr) - 1);
            $pass .= $arr[$index];
        }

        return $pass;
    }



    /**************************************************************************************************
     * Private functions
     *************************************************************************************************/

    private function mcryptEncrypt($data)
    {
        $data = serialize($data);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);

        $key = pack('H*', self::$hashKey);
        $mac = hash_hmac('sha256', $data, substr(bin2hex($key), -32));

        $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data.$mac, MCRYPT_MODE_CBC, $iv);
        $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);

        return $encoded;
    }

    /**
     * @return EncryptionModel|null
     */
    public static function getInstance($encryptionType = self::ENCRYPTION_TYPE_MD5)
    {
        if (!self::$_instance instanceof EncryptionModel) {

            EncryptionModel::setEncryptionType($encryptionType);

            self::$_instance = new self;
        }

        return self::$_instance;
    }
}