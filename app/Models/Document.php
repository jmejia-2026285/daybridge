<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'area_id',
    'title',
    'description',
    'category',
    'file_path',
    'file_name',
    'mime_type',
    'file_size',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn (Builder $query): Builder => $query
            ->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhere('file_name', 'like', "%{$term}%"));
    }

    public function formattedSize(): string
    {
        if ($this->file_size >= 1_048_576) {
            return number_format($this->file_size / 1_048_576, 1, ',', '.').' MB';
        }

        if ($this->file_size >= 1024) {
            return number_format($this->file_size / 1024, 0, ',', '.').' KB';
        }

        return $this->file_size.' B';
    }

    public function extension(): string
    {
        return Str::upper(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'file_size' => 'integer',
        ];
    }
}
