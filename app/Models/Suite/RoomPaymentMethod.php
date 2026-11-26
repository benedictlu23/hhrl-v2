<?php

namespace App\Models\Suite;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'code',
    'account_name',
    'account_number',
    'qr_code_path',
    'instructions',
    'is_enabled',
    'sort_order',
])]
class RoomPaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'room_payment_methods';

    protected $appends = ['qr_code_url'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        if (! $this->qr_code_path) {
            return null;
        }

        if (str_starts_with($this->qr_code_path, 'http://') || str_starts_with($this->qr_code_path, 'https://')) {
            return $this->qr_code_path;
        }

        return asset('storage/'.ltrim($this->qr_code_path, '/'));
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true)->orderBy('sort_order')->orderBy('name');
    }
}
