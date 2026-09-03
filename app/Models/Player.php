<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('players')]
#[Fillable([
    'name',
    'email',
    'mobile',
    'address',
    'slug',
])]
class Player extends Model
{
    use HasFactory, HasSlug;

    protected $appends = [];

    protected function casts(): array
    {
        return [
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->saveSlugsTo('slug')
            ->generateSlugsFrom("name")
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(fn() => Str::lower(Str::random(5)));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function gamePlays(): HasMany
    {
        return $this->hasMany(GamePlay::class);
    }

    public function gamePlayResults(): HasMany
    {
        return $this->hasMany(GamePlayResult::class);
    }

    public function gamePlayRankers(): HasMany
    {
        return $this->hasMany(GamePlayRanker::class);
    }
}
