<?php

namespace Datomatic\ActiveCampaign\Enums;

/**
 * @see https://developers.activecampaign.com/reference/filters
 */
enum FilterOperator: string
{
    case Equals = 'eq';
    case NotEquals = 'neq';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case Contains = 'contains';
    case StartsWith = 'starts_with';
}
