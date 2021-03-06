<?php

namespace MrClay\Crypt;

use MrClay\Crypt\Cipher\Base;
use MrClay\Crypt\ByteString;

/**
 * Simplified encryption/decryption model, using uniquely derived keys for each encryption and structured
 * cipherText storage container for flexible encoding (e.g. in Base64).
 *
 * The default cipher is AES-256 (aka Rijndael-256) in counter mode.
 * @link http://www.daemonology.net/blog/2009-06-11-cryptographic-right-answers.html
 *
 * @author Steve Clay <steve@mrclay.org>
 * @license http://www.opensource.org/licenses/mit-license.php  MIT License
 */
class Encryption {

    /**
     * @var Base
     */
    protected $cipher;

    /**
     * @var ByteString
     */
    protected $key;

    /**
     * @param ByteString $key
     * @param Cipher\Base|null $cipher
     * @throws \InvalidArgumentException
     */
    public function __construct(ByteString $key, Base $cipher = null)
    {
        $this->key = $key;
        if (! $cipher) {
            $cipher = new Cipher\Rijndael256();
        }
        $requiredSize = $cipher->getKeySize();
        if ($this->key->getSize() < $requiredSize) {
            throw new \InvalidArgumentException("Given key must be $requiredSize bytes");
        }
        $this->cipher = $cipher;
    }

    /**
     * Encrypt plainText
     *
     * @param $plainText
     * @return Container values required to decrypt [cipherText, IV, HMAC(cipherText+IV)]
     */
    public function encrypt($plainText)
    {
        $this->cipher->setKey($this->key);
        $cipherText = $this->cipher->encrypt($plainText);
        $iv = $this->cipher->getIv(); // was created just before encryption, must store

        // we choose encrypt-then-HMAC for several reasons
        // @link http://www.daemonology.net/blog/2009-06-24-encrypt-then-mac.html
        $hmac = new Hmac($this->key);
        $mac = $hmac->generateMac($cipherText->getBytes() . $iv->getBytes());
        
        return new Container(array($cipherText, $iv, $mac));
    }

    /**
     * Decrypt a storage container to plainText
     *
     * @param Container $cont values generated by encrypt() [cipherText, IV, HMAC(cipherText+IV)]
     * @return string|false false on failure
     */
    public function decrypt(Container $cont)
    {
        if (count($cont) !== 3) {
            return false;
        }
        list($cipherText, $iv, $storedMac) = $cont;
        /* @var ByteString $cipherText */
        /* @var ByteString $iv */
        /* @var ByteString $storedMac */
        
        $hmac = new Hmac($this->key);
        $mac = $hmac->generateMac($cipherText->getBytes() . $iv->getBytes());
        
        if (! $mac->equals($storedMac)) {
            return false; // something was tampered with, don't even need to decrypt
        }
        $this->cipher->setKey($this->key);
        $this->cipher->setIv($iv);
        return $this->cipher->decrypt($cipherText);
    }
}
