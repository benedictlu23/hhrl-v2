<?php

namespace Database\Seeders;

use App\Models\Suite\Room;
use App\Models\Suite\RoomAddonService;
use App\Models\Suite\RoomPaymentMethod;
use Illuminate\Database\Seeder;

class BookingEnhancementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed default dynamic payment methods
        $paymentMethods = [
            [
                'name' => 'GCash',
                'code' => 'gcash',
                'account_name' => 'Agro-Inland Resort',
                'account_number' => '0912-345-6789',
                'qr_code_path' => null,
                'instructions' => 'Scan the GCash QR code or transfer to the mobile number. Upload screenshot with reference number.',
                'is_enabled' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'BDO Unibank',
                'code' => 'bank_transfer',
                'account_name' => 'Agro-Inland Corp.',
                'account_number' => '0012-3456-7890',
                'qr_code_path' => null,
                'instructions' => 'Direct deposit or bank transfer to BDO Unibank. Submit transaction receipt for validation.',
                'is_enabled' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Cash on Arrival',
                'code' => 'cash',
                'account_name' => null,
                'account_number' => null,
                'qr_code_path' => null,
                'instructions' => 'Pay in cash directly at the resort reception upon check-in.',
                'is_enabled' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($paymentMethods as $pm) {
            RoomPaymentMethod::updateOrCreate(
                ['code' => $pm['code']],
                $pm
            );
        }

        // 2. Attach existing addons to existing rooms with default limits
        $addons = RoomAddonService::all();
        $rooms = Room::all();

        foreach ($rooms as $room) {
            foreach ($addons as $addon) {
                $maxQty = match ($addon->name) {
                    'Extra Bed' => 2,
                    'BBQ Package' => 2,
                    'Farm-Fresh Breakfast' => 10,
                    'Fishing Rod Rental' => 4,
                    default => 5,
                };

                $room->addonServices()->syncWithoutDetaching([
                    $addon->id => ['max_quantity' => $maxQty],
                ]);
            }
        }
    }
}
