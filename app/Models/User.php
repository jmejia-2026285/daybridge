<?php

namespace App\Models;

use App\Enums\ItDeskRole;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'it_desk_role', 'is_active', 'area_id', 'job_title', 'phone', 'birth_date', 'joined_at', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isEditor(): bool
    {
        return $this->role === UserRole::Editor;
    }

    public function canManageContent(): bool
    {
        return $this->role->canManageContent();
    }

    public function isItDeskMember(): bool
    {
        return $this->it_desk_role !== null;
    }

    public function isItDeskAdmin(): bool
    {
        return $this->it_desk_role === ItDeskRole::Admin;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function birthdayWishesReceived(): HasMany
    {
        return $this->hasMany(BirthdayWish::class, 'recipient_id');
    }

    public function birthdayWishesSent(): HasMany
    {
        return $this->hasMany(BirthdayWish::class, 'sender_id');
    }

    public function isBirthdayToday(): bool
    {
        return $this->birth_date !== null
            && $this->birth_date->month === today()->month
            && $this->birth_date->day === today()->day;
    }

    public function nextBirthday(): Carbon
    {
        $next = today()->startOfDay();
        $next->setDate($next->year, $this->birth_date->month, $this->birth_date->day);

        return $next->isBefore(today()) ? $next->addYear() : $next;
    }

    public function daysUntilBirthday(): int
    {
        if ($this->birth_date === null) {
            return 365;
        }

        return (int) today()->startOfDay()->diffInDays($this->nextBirthday());
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];

        return Str::upper(Str::substr($parts[0] ?? '', 0, 1).Str::substr($parts[1] ?? '', 0, 1));
    }

    public function yearsAtCompany(): int
    {
        return $this->joined_at === null ? 0 : (int) $this->joined_at->diffInYears(today());
    }

    public static function celebratingToday(): Collection
    {
        return static::query()
            ->active()
            ->with([
                'area',
                'birthdayWishesReceived' => fn (HasMany $query) => $query
                    ->where('year', today()->year)
                    ->with('sender.area')
                    ->latest(),
            ])
            ->whereNotNull('birth_date')
            ->whereMonth('birth_date', today()->month)
            ->whereDay('birth_date', today()->day)
            ->orderBy('name')
            ->get();
    }

    public static function upcomingBirthdays(int $days = 30): Collection
    {
        return static::query()
            ->active()
            ->with('area')
            ->whereNotNull('birth_date')
            ->get()
            ->filter(fn (User $user): bool => $user->daysUntilBirthday() > 0 && $user->daysUntilBirthday() <= $days)
            ->sortBy(fn (User $user): int => $user->daysUntilBirthday())
            ->values();
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->orderByDesc('created_at');
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function notificationsCount(): int
    {
        return $this->unreadNotifications()->count();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'joined_at' => 'date',
            'role' => UserRole::class,
            'it_desk_role' => ItDeskRole::class,
            'is_active' => 'boolean',
            'moodle_user_id' => 'integer',
        ];
    }
}
