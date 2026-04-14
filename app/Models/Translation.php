<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'locale', 'content'];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'translation_tag');
    }
}
