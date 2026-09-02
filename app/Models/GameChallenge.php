<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('game_challenges')]
#[Fillable(['game_id', 'game_difficulty_id', 'game_date', 'board', 'starts_at', 'ends_at'])]
class GameChallenge extends Model
{
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'game_date' => 'date',
            'board' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
            ->usingSuffixGenerator(fn () => Str::lower(Str::random(5)).'-'.now()->format('HisdmY'));
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function gameDifficulty(): BelongsTo
    {
        return $this->belongsTo(GameDifficulty::class, 'game_difficulty_id');
    }

    public function gameScores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }
}
