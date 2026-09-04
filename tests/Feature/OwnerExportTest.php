<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_route_cache_keeps_owner_page_and_csv_export_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'landlord', 'name' => 'Fallback Owner']);
        $routes = new \Illuminate\Routing\RouteCollection;
        foreach (app('router')->getRoutes() as $route) {
            if ($route->getName() !== 'admin.landlord.excel.list') {
                $routes->add($route);
            }
        }
        app('router')->setRoutes($routes);
        app('url')->setRoutes($routes);
        $this->actingAs($admin)->get(route('admin.landlord.index'))->assertOk()->assertSee('format=csv');
        $response = $this->get(route('admin.landlord.pdf.list', ['format' => 'csv']));
        $response->assertOk()->assertDownload();
        $this->assertStringContainsString('Fallback Owner', $response->streamedContent());
    }

    public function test_export_includes_each_unit_and_respects_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'name' => 'Export Owner']);
        User::factory()->create(['role' => 'landlord', 'name' => 'Other Owner']);
        $building = Building::create(['building_name' => 'Marina Tower', 'address' => 'Marina', 'city' => 'Dubai', 'state' => 'Dubai', 'country' => 'UAE']);
        foreach (['501', '502'] as $number) {
            Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => $number]);
        }
        $response = $this->actingAs($admin)->get(route('admin.landlord.excel.list', ['search' => 'Export Owner']));
        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Building', $csv);
        $this->assertSame(2, substr_count($csv, 'Marina Tower'));
        $this->assertStringContainsString('501', $csv);
        $this->assertStringContainsString('502', $csv);
        $this->assertStringNotContainsString('Other Owner', $csv);
        $this->get(route('admin.landlord.pdf.list', ['search' => 'Export Owner']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($owner)->get(route('admin.landlord.excel.list'))->assertForbidden();
    }
}
