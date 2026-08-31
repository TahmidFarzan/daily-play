<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class SystemHelper
{
    public static function toOptions(array $items): Collection
    {
        return collect($items)->map(fn($item) => (object) [
            'id'   => $item,
            'name' => $item,
        ]);
    }
}
