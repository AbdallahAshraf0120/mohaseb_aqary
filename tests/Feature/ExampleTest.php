<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_public_and_app_routes_are_separated(): void
    {
        $this->assertTrue(Route::has('site.home'));
        $this->assertTrue(Route::has('site.projects'));
        $this->assertTrue(Route::has('site.contact'));
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('home'));
        $this->assertTrue(Route::has('projects.index'));
        $this->assertTrue(Route::has('website.brand.update'));

        $this->assertSame('/app/login', route('login', absolute: false));
        $this->assertSame('/', route('site.home', absolute: false));
        $this->get('/app/login')->assertOk();
    }
}
