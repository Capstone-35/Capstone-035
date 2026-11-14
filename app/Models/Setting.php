<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'updated_by',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return static::getValue($key, $default);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();
        if (! $row) {
            return $default;
        }

        return match ($row->type) {
            'int' => (int) $row->value,
            'float' => (float) $row->value,
            'bool' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $row->value, true) ?? $default,
            default => $row->value,
        };
    }

    public static function setValue(string $key, mixed $value, string $type = 'string', ?string $description = null, ?int $updatedBy = null): self
    {
        $serialized = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);

        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $serialized,
                'type' => $type,
                'description' => $description,
                'updated_by' => $updatedBy,
            ]
        );
    }
}
