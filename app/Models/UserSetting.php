<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasUlids;

    protected $fillable = ['user_id', 'key', 'value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function get(int|string $userId, string $key, mixed $default = null): mixed
    {
        $setting = static::where('user_id', $userId)->where('key', $key)->value('value');

        return $setting ?? $default;
    }

    public static function set(int|string $userId, string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value],
        );
    }
}
