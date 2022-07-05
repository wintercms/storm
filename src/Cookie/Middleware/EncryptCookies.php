<?php namespace Winter\Storm\Cookie\Middleware;

use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Cookie\Middleware\EncryptCookies as EncryptCookiesBase;
use Winter\Storm\Support\Facades\Config;

class EncryptCookies extends EncryptCookiesBase
{
    /**
     * @inheritDoc
     */
    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);

        // Find unencrypted cookies as specified by the configuration
        $except = Config::get('cookie.unencryptedCookies', []);

        $this->disableFor($except);
    }
}
