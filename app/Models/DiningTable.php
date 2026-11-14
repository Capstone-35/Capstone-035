<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    use HasFactory;

    protected $table = 'tables';

    protected $fillable = [
        'number',
        'label',
        'qr_token',
        'is_active',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function qrUrl(): string
    {
        return route('table.scan', ['qr_token' => $this->qr_token]);
    }

    public static function generateToken(string $tableNumber): string
    {
        return 'meja-'.strtolower($tableNumber).'-'.Str::random(32);
    }

    public function regenerateToken(): self
    {
        $this->update([
            'qr_token' => self::generateToken($this->number),
        ]);

        return $this;
    }
}
