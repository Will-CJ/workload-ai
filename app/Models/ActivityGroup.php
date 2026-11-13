<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityGroup extends Model
{
    protected $fillable = ['code', 'label', 'description'];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public static function codes(): array
    {
        return static::query()->orderBy('id')->pluck('code')->all();
    }
}
