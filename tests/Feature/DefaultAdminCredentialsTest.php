<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultAdminCredentialsTest extends TestCase
{
    public function test_database_seeder_creates_default_admin_with_requested_password(): void
    {
        $this->seed();

        $user = User::where('email', 'admin@admin.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('admin123', $user->password));
    }
}
