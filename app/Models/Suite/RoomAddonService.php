<?php

namespace App\Models\Suite;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'icon', 'pricing_type', 'price', 'is_active', 'sort_order'])]
class RoomAddonService extends Model
{
    /** @use HasFactory<AddonServiceFactory> */
    use HasFactory;

    protected $table = 'room_addon_services';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function bookingAddons(): HasMany
    {
        return $this->hasMany(RoomBookingAddon::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_room_addon_services', 'addon_service_id', 'room_id')
            ->withPivot('max_quantity')
            ->withTimestamps();
    }
}
