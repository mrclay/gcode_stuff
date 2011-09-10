<?php

namespace MrClay\Crypt\Encoding;

use MrClay\Crypt\ByteString;

class Base64 implements EncodingInterface {
    /**
     * @param \MrClay\Crypt\ByteString $bytes
     * @return string
     */
    public function encode(ByteString $bytes)
    {
        return base64_encode($bytes->getBytes());
    }

    /**
     * @param string $encoded
     * @return false|\MrClay\Crypt\ByteString
     */
    public function decode($encoded)
    {
        $decoded = base64_decode($encoded);
        return ($decoded === false)
            ? false
            : new ByteString($decoded);
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return ".";
    }
}
