<?php

namespace App\Enums;

enum PaginationSize: int
{
    case SMALLEST = 5;
    case SMALL = 10;
    case MEDIUM = 25;
    case LARGE = 50;
    case XLARGE = 100;
}
