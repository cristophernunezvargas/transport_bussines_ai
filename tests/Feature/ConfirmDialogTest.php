<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ConfirmDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_layout_includes_the_shared_confirmation_dialog(): void
    {
        $this->actingAs(User::factory()->viewer()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('confirm-action', false)
            ->assertSee('Esta acción no se puede deshacer.');
    }

    public function test_no_view_uses_the_browsers_native_confirm_dialog(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            $this->assertStringNotContainsString(
                'wire:confirm',
                $file->getContents(),
                "{$file->getRelativePathname()} still uses the native confirm dialog; dispatch 'confirm-action' instead."
            );
        }
    }
}
