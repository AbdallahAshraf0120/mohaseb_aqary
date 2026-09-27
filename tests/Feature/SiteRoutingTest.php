<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SiteRoutingTest extends TestCase
{
    public function test_public_and_admin_route_names_do_not_collide(): void
    {
        $names = collect(Route::getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->countBy()
            ->filter(fn (int $count) => $count > 1);

        $this->assertTrue($names->isEmpty(), 'Duplicate route names: '.$names->keys()->implode(', '));
    }

    public function test_public_site_and_admin_app_are_separated(): void
    {
        $this->assertTrue(Route::has('site.home'));
        $this->assertTrue(Route::has('site.projects'));
        $this->assertTrue(Route::has('site.projects.show'));
        $this->assertTrue(Route::has('site.about'));
        $this->assertTrue(Route::has('site.contact'));
        $this->assertTrue(Route::has('site.contact.store'));

        $this->assertTrue(Route::has('home'));
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('projects.index'));
        $this->assertTrue(Route::has('website.index'));
        $this->assertTrue(Route::has('website.brand.update'));
        $this->assertTrue(Route::has('crm-leads.index'));

        $this->assertSame('/', route('site.home', absolute: false));
        $this->assertSame('/projects', route('site.projects', absolute: false));
        $this->assertSame('/about', route('site.about', absolute: false));
        $this->assertSame('/contact', route('site.contact', absolute: false));
        $this->assertSame('/app/login', route('login', absolute: false));
        $this->assertSame('/app/projects', route('projects.index', absolute: false));
        $this->assertSame('/app/website', route('website.index', absolute: false));
        $this->assertSame('/app/crm/leads', route('crm-leads.index', absolute: false));
    }

    public function test_public_pages_respond_successfully(): void
    {
        // phpunit.xml يستخدم sqlite::memory — بدون امتداد pdo_sqlite نتخطى اختبارات HTTP.
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for HTTP feature tests.');
        }

        $this->get('/')->assertOk();
        $this->get('/projects')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/contact')->assertOk();
        $this->get('/app/login')->assertOk();
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->get('/app/website')->assertRedirect('/app/login');
        $this->get('/app/projects')->assertRedirect('/app/login');
        $this->get('/app/crm/leads')->assertRedirect('/app/login');
    }

    public function test_website_brand_permission_is_mapped(): void
    {
        $map = config('route-permissions');

        $this->assertArrayHasKey('website.brand.update', $map);
        $this->assertSame('website.manage', $map['website.brand.update']);
        $this->assertArrayHasKey('website.index', $map);
        $this->assertArrayHasKey('website.update', $map);
        $this->assertArrayHasKey('website.projects.toggle', $map);
    }
}
