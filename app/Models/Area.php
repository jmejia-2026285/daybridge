<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'color', 'sort_order'])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    protected function color(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : '#0F3D3E',
            set: fn (string $value): string => preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : '#0F3D3E',
        );
    }
}
