<?php

namespace Database\Seeders;

use App\Models\Rider;
use Illuminate\Database\Seeder;

/**
 * Populates the riders table, which had zero rows since the delivery
 * migrations were added — without at least one rider, the admin Deliveries
 * assignment dropdown (Rider::where('status','active')->where('available', true))
 * is always empty and no order can ever progress past "Order Placed".
 *
 * vehicle_type is constrained to motorcycle|bicycle|van by StoreRiderRequest
 * and the riders table default; no other value is valid.
 */
class RiderSeeder extends Seeder
{
    public function run(): void
    {
        $riders = [
            [
                'name' => 'Brian Kiplagat',
                'email' => 'brian.kiplagat@rcfashion-riders.test',
                'phone' => '0712345671',
                'vehicle_type' => 'motorcycle',
                'number_plate' => 'KMDA 245J',
                'available' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Emmanuel Njoroge',
                'email' => 'emmanuel.njoroge@rcfashion-riders.test',
                'phone' => '0722456782',
                'vehicle_type' => 'motorcycle',
                'number_plate' => 'KMEB 118F',
                'available' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Susan Wambui',
                'email' => 'susan.wambui@rcfashion-riders.test',
                'phone' => '0733567893',
                'vehicle_type' => 'van',
                'number_plate' => 'KDD 902P',
                'available' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Kevin Omondi',
                'email' => 'kevin.omondi@rcfashion-riders.test',
                'phone' => '0700678904',
                'vehicle_type' => 'motorcycle',
                'number_plate' => 'KMFC 331L',
                'available' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Alice Chepkoech',
                'email' => 'alice.chepkoech@rcfashion-riders.test',
                'phone' => '0788789015',
                'vehicle_type' => 'bicycle',
                'number_plate' => null,
                'available' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Joseph Mutua',
                'email' => 'joseph.mutua@rcfashion-riders.test',
                'phone' => '0790890126',
                'vehicle_type' => 'van',
                'number_plate' => 'KDG 447R',
                'available' => true,
                'status' => 'active',
            ],
        ];

        foreach ($riders as $rider) {
            Rider::firstOrCreate(['email' => $rider['email']], $rider);
        }

        $this->command?->info('Riders ensured: ' . count($riders) . ' active riders seeded.');
    }
}
