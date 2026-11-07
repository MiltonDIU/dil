<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every admin screen must render for an admin, and be closed to everyone else.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function screens(): array
    {
        return [
            'dashboard' => ['/admin'],
            'courses' => ['/admin/courses'],
            'pages' => ['/admin/pages'],
            'news' => ['/admin/posts'],
            'categories' => ['/admin/categories'],
            'menus' => ['/admin/menus'],
            'blocks' => ['/admin/blocks'],
            'slides' => ['/admin/slides'],
            'forms' => ['/admin/forms'],
            'submissions' => ['/admin/form-submissions'],
            'redirects' => ['/admin/redirects'],
            'users' => ['/admin/users'],
            'media' => ['/admin/media'],
            'ai decisions' => ['/admin/ai-mappings'],
            'settings' => ['/admin/manage-settings'],
        ];
    }

    #[DataProvider('screens')]
    public function test_admin_screen_renders(string $url): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get($url)->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/courses')->assertRedirect();
    }

    public function test_non_admin_is_refused(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/courses')->assertForbidden();
    }

    public function test_blocked_admin_is_refused(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'blocked' => true]);

        $this->actingAs($user)->get('/admin/courses')->assertForbidden();
    }
}
