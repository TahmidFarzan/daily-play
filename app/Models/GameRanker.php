<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('game_rankers')]
#[Fillable([
    'game_score_id',
    'player_id',
    'rank',
    'slug',
])]
class GameRanker extends Model
{
    use HasFactory, HasSlug;

    protected $appends = [];

    protected function casts(): array
    {
        return [
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
            'rank'              => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->saveSlugsTo('slug')
            ->generateSlugsFrom(function ($model) {
                $mainSlug = Str::random(5).'-'.now()->timestamp;
                return "{$mainSlug}";
            })
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(fn() => Str::lower(Str::random(5)));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function gameScore(): BelongsTo
    {
        return $this->belongsTo(GameScore::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
