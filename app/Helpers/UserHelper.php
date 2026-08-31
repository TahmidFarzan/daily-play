<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class UserHelper
{
    private const USER_GENDER_MALE   = 'Male';
    private const USER_GENDER_FEMALE = 'Female';
    private const USER_GENDER_OTHER  = 'Other';

    private const USER_RELIGION_ISLAM     = 'Islam';
    private const USER_RELIGION_HINDU     = 'Hindu';
    private const USER_RELIGION_CHRISTIAN = 'Christian';
    private const USER_RELIGION_OTHER     = 'Other';

    private const USER_MARITAL_SINGLE    = 'Single';
    private const USER_MARITAL_MARRIED   = 'Married';
    private const USER_MARITAL_DIVORCED  = 'Divorced';
    private const USER_MARITAL_SEPARATED = 'Separated';
    private const USER_MARITAL_OTHER     = 'Other';


    public static function genders(): Collection
    {
        return SystemHelper::toOptions([
            self::USER_GENDER_MALE,
            self::USER_GENDER_FEMALE,
            self::USER_GENDER_OTHER,
        ]);
    }

    public static function religions(): Collection
    {
        return SystemHelper::toOptions([
            self::USER_RELIGION_ISLAM,
            self::USER_RELIGION_HINDU,
            self::USER_RELIGION_CHRISTIAN,
            self::USER_RELIGION_OTHER,
        ]);
    }

    public static function maritalStatuses(): Collection
    {
        return SystemHelper::toOptions([
            self::USER_MARITAL_SINGLE,
            self::USER_MARITAL_MARRIED,
            self::USER_MARITAL_DIVORCED,
            self::USER_MARITAL_SEPARATED,
            self::USER_MARITAL_OTHER,
        ]);
    }
}
