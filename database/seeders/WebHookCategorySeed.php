<?php

namespace Database\Seeders;

use App\Models\WebhookCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebHookCategorySeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'ariChanges',
            'bookingAny',
            'bookingUnmappedRoom',
            'bookingUnmappedRate',
            'message',
            'review',
            'reservationRequest',
            'syncError',
        ];

        foreach ($categories as $category) {
            WebhookCategory::create(['name' => $category]);
        }
    }
}
