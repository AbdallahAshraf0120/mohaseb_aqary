<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table): void {
            $table->id();

            $table->string('brand')->nullable();
            $table->string('tagline')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            $table->string('hero_eyebrow')->nullable();
            $table->text('hero_lead')->nullable();
            $table->string('hero_cta_primary')->nullable();
            $table->string('hero_cta_secondary')->nullable();
            $table->json('marquee_items')->nullable();
            $table->string('home_projects_title')->nullable();
            $table->string('home_projects_subtitle')->nullable();
            $table->unsignedTinyInteger('home_projects_limit')->default(6);
            $table->string('cta_title')->nullable();
            $table->text('cta_text')->nullable();
            $table->string('cta_button')->nullable();

            $table->string('about_title')->nullable();
            $table->text('about_intro')->nullable();
            $table->string('about_vision_title')->nullable();
            $table->text('about_vision')->nullable();

            $table->string('trust_title')->nullable();
            $table->text('trust_subtitle')->nullable();
            $table->json('trust_points')->nullable();
            $table->json('services')->nullable();

            $table->string('contact_title')->nullable();
            $table->text('contact_intro')->nullable();
            $table->string('contact_success')->nullable();

            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
