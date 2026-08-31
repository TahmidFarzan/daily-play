<?php
namespace App\Models;

use App\Helpers\MediaHelper;
use App\Observers\UserObserver;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('users')]
#[Fillable([
        'name', 'email', 'slug', 'password', 'is_default',
        'marital_status', 'religion', 'gender', 'mobile', 'birth_date',
        'address', 'created_by_id', 'is_super_admin',
    ])]
#[Hidden([
        'password', 'remember_token', 'is_default',
    ])]
#[UsePolicy(UserPolicy::class)]
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity, HasSlug, InteractsWithMedia;

    protected $appends = [
        'age', 'is_active', 'media_collection_name',
    ];

    protected function casts(): array
    {
        return [
            'is_super_admin'    => 'boolean',
            'email_verified_at' => 'datetime',
            'birth_date'        => 'date',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'password',
                'updated_at',
                'deleted_at',
                'email_verified_at',
                'is_default',
                'mobile',
                'gender',
                'religion',
                'address',
                'marital_status',
                'is_super_admin',
            ])
            ->useLogName('User')
            ->setDescriptionForEvent(fn(string $eventName) => "The record has been {$eventName}.")
            ->logOnlyDirty()
            ->logExcept([
                'id',
                'created_by_id',
                'created_at',
                'remember_token',
            ])
            ->dontLogEmptyChanges();
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection($this->media_collection_name);
    }

    public function registerMediaConversions($spatieMedia = null): void
    {
        $this->addMediaConversion(MediaHelper::DEFAULT_CONVERSION)
            ->format(MediaHelper::DEFAULT_CONVERSION_FORMAT)
            ->quality(100)
            ->performOnCollections($this->media_collection_name)
            ->queued();
    }

    public function getMediaCollectionNameAttribute(): string
    {
        return "User";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ($this->deleted_at == null) ? true : false;
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by_id');
    }

    public function userPermissions(): BelongsToMany
    {
        return $this->belongsToMany(UserPermission::class, 'user_user_permission');
    }

    public function latestActivityLog(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')->latestOfMany();
    }

    public function profileImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', $this->media_collection_name)
            ->whereJsonContains('custom_properties->role', MediaHelper::ROLE_PROFILE_IMAGE);
    }

    public function hasUserPermission(string $module, string $access): bool
    {
        return $this->userPermissions()
            ->where('module', $module)
            ->where('access', $access)
            ->exists();
    }
}
