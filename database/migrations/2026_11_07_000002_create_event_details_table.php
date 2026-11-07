<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_details', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Timeless Asia');
            $table->string('subtitle')->default('Gems & Jewelry Exhibition');
            $table->string('edition')->default('4th Edition');
            $table->string('date_display')->default('JANUARY 22 - 24 , 2027');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('venue')->default('Lotte Hotel Yangon Level 2');
            $table->string('invitation_text')->default('Cordially Requests The Honor Of Your Presence');
            $table->string('contact_phone')->nullable()->default('09450011223');
            $table->string('contact_email')->nullable();
            $table->string('logo_path')->nullable()->default('images/timeless-asia-logo-white.png');
            $table->string('banner_path')->nullable()->default('images/timeless-asia-banner.jpg');
            $table->string('footer_text')->nullable()->default('Timeless Asia • 4th Edition 2027');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert initial default event details
        DB::table('event_details')->insert([
            'title' => 'Timeless Asia',
            'subtitle' => 'Gems & Jewelry Exhibition',
            'edition' => '4th Edition',
            'date_display' => 'JANUARY 22 - 24 , 2027',
            'start_date' => '2027-01-22',
            'end_date' => '2027-01-24',
            'venue' => 'Lotte Hotel Yangon Level 2',
            'invitation_text' => 'Cordially Requests The Honor Of Your Presence',
            'contact_phone' => '09450011223',
            'logo_path' => 'images/timeless-asia-logo-white.png',
            'banner_path' => 'images/timeless-asia-banner.jpg',
            'footer_text' => 'Timeless Asia • 4th Edition 2027',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('event_details');
    }
};
