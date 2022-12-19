<?php

namespace Datomatic\ActiveCampaign\Exceptions;

use RuntimeException;

class InvalidConfig extends RuntimeException
{
    protected static $configFile = 'active-campaign';

    public static function missingParam(string $param): self
    {
        return new self('You need to set '.$param.' on '.self::$configFile.'.php config file');
    }

    public static function paramHasWrongType(string $param, string $type): self
    {
        return new self('The param '.$param.' on '.self::$configFile.'.php config file must be a '.$type);
    }
}
