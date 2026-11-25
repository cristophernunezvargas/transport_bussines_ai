<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public registration is disabled (config/fortify.php): logins are created by
 * an admin on the Users screen, so nobody can sign themselves up.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_registration_screen_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_nobody_can_register_themselves(): void
    {
        $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_the_login_page_no_longer_offers_to_sign_up(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Sign up');
    }
}
