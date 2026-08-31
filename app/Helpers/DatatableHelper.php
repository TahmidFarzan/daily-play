<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class DatatableHelper
{
    private const PER_PAGE_10  = 10;
    private const PER_PAGE_25  = 25;
    private const PER_PAGE_50  = 50;
    private const PER_PAGE_100 = 100;

    public static function perPages(): Collection
    {
        return SystemHelper::toOptions([
            self::PER_PAGE_10,
            self::PER_PAGE_25,
            self::PER_PAGE_50,
            self::PER_PAGE_100,
        ]);
    }
}
