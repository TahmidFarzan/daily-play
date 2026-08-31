<?php
namespace App\Models;

use App\Helpers\UserPermissionHelper;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('user_permissions')]
#[Fillable(['module', 'access'])]
class UserPermission extends Model
{
    use HasFactory, HasSlug;

    protected $appends = [
        "name",
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->saveSlugsTo('slug')
            ->generateSlugsFrom(["module", "access"])
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(fn() => Str::lower(Str::random(5)));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getNameAttribute(): ?string
    {
        return UserPermissionHelper::modulePermissingNameGenerates($this->module, $this->access);
    }


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_user_permission');
    }
}
