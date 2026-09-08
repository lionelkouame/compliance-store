<?php

namespace App\Infrastructure\Service;

enum Comparator: string
{
    case Equal='equal';
    case NotEqual = 'not_equal';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThanOrEqual = 'less_than_or_equal';
}
