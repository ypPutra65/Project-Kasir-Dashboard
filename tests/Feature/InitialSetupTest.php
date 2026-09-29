<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialSetupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test database connectivity and migrations.
     */
    public function test_database_connection_and_users_table_migrated(): void
    {
        $this->assertNotNull(DB::connection()->getPdo());
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('users'));
    }

    /**
     * Test UserSeeder creates the default owner account correctly.
     */
    public function test_user_seeder_creates_owner_account(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'owner@budhelamongan.local',
            'name' => 'Owner Budhe Lamongan',
        ]);

        $owner = User::where('email', 'owner@budhelamongan.local')->first();
        $this->assertNotNull($owner);
        $this->assertTrue(Hash::check('password', $owner->password));
        $this->assertNotNull($owner->email_verified_at);
    }

    /**
     * Test root route redirects to admin panel.
     */
    public function test_root_route_redirects_to_admin(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/admin');
    }

    /**
     * Test Filament admin login page is reachable for unauthenticated users.
     */
    public function test_filament_admin_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');
        $response->assertSuccessful();
    }

    /**
     * Test unauthenticated access to admin dashboard redirects to login.
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    /**
     * Test owner user can authenticate and access Filament admin panel.
     */
    public function test_owner_user_can_access_filament_panel(): void
    {
        $this->seed(UserSeeder::class);

        $owner = User::where('email', 'owner@budhelamongan.local')->first();

        $response = $this->actingAs($owner)->get('/admin');
        $response->assertSuccessful();
    }
}
