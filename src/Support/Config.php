<?php

namespace Datomatic\ActiveCampaign\Support;

use Datomatic\ActiveCampaign\Exceptions\InvalidConfig;

abstract class Config
{
    protected static function getParam(string $paramName, ?callable $function = null): mixed
    {
        $param = config('active-campaign.'.$paramName);

        throw_if(is_null($param), InvalidConfig::missingParam($paramName));

        if (! is_null($function)) {
            $function($param, $paramName);
        }

        return $param;
    }

    protected static function getStringParam(string $param): string
    {
        return self::getParam($param, function (mixed $param, string $paramName) {
            throw_if(! is_string($param), InvalidConfig::paramHasWrongType($paramName, 'string'));
        });
    }

    protected static function getIntParam(string $param): int
    {
        return self::getParam($param, function (mixed $param, string $paramName) {
            throw_if(! is_int($param), InvalidConfig::paramHasWrongType($paramName, 'integer'));
        });
    }

    protected static function getArrayParam(string $param): array
    {
        return self::getParam($param, function (mixed $param, string $paramName) {
            throw_if(! is_array($param), InvalidConfig::paramHasWrongType($paramName, 'array'));
        });
    }
}
