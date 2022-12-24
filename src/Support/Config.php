<?php

namespace Datomatic\ActiveCampaign\Support;

use Datomatic\ActiveCampaign\Exceptions\InvalidConfig;

abstract class Config
{
    protected static function getParam(string $paramName, ?callable $function = null): mixed
    {
        $param = config('active-campaign.'.$paramName);

        throw_if(empty($param), InvalidConfig::missingParam($paramName));

        if (! is_null($function)) {
            $function($param);
        }

        return $param;
    }

    protected static function getStringParam(string $param): string
    {
        return self::getParam($param, function (string $param) {
            throw_if(! is_string($param), InvalidConfig::paramHasWrongType($param, 'string'));
        });
    }

    protected static function getIntParam(string $param): int
    {
        return self::getParam($param, function (int $param) {
            throw_if(! is_int($param), InvalidConfig::paramHasWrongType($param, 'string'));
        });
    }

    protected static function getArrayParam(string $param): array
    {
        $param = self::getParam($param);

        throw_if(! is_array($param), InvalidConfig::paramHasWrongType($param, 'array'));

        return $param;
    }
}
