<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Course;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Lesson;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FrontEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_with_no_content(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_published_page_renders_and_draft_404s(): void
    {
        $live = Page::create(['title' => 'About', 'slug' => 'about', 'body' => '<p>Hello</p>', 'published' => true]);
        $draft = Page::create(['title' => 'Draft', 'slug' => 'draft', 'body' => '<p>Hi</p>', 'published' => false]);

        $this->get("/{$live->slug}")->assertOk()->assertSee('Hello', false);
        $this->get("/{$draft->slug}")->assertNotFound();
    }

    public function test_course_page_shows_only_known_facts(): void
    {
        $course = Course::create([
            'title' => 'German A1', 'slug' => 'german-a1', 'published' => true,
            'body' => '<p>Course body</p>', 'language' => 'German',
            'fee_regular' => 12000, 'duration_months' => 3,
            // fee_outside deliberately null — it must not appear at all.
        ]);

        $response = $this->get("/courses/{$course->slug}");

        $response->assertOk()
            ->assertSee('Tk. 12,000')
            ->assertSee('German')
            ->assertDontSee('Outside students');
    }

    public function test_course_page_shows_published_lessons_and_hides_drafts(): void
    {
        $course = Course::create([
            'title' => 'German A1', 'slug' => 'german-a1', 'published' => true,
            'body' => '<p>Course body</p>',
        ]);

        Lesson::create(['course_id' => $course->id, 'title' => 'Introduction & Basics',
            'duration_label' => 'Weeks 1–2', 'body' => '<p>Alphabet and greetings</p>',
            'published' => true, 'ordering' => 10]);
        Lesson::create(['course_id' => $course->id, 'title' => 'Secret draft lesson',
            'published' => false, 'ordering' => 20]);

        $this->get("/courses/{$course->slug}")
            ->assertOk()
            ->assertSee('Lesson breakdown')
            ->assertSee('Introduction &amp; Basics', false)
            ->assertSee('Weeks 1–2')
            ->assertSee('Alphabet and greetings')
            ->assertDontSee('Secret draft lesson');
    }

    public function test_course_page_has_no_lesson_breakdown_when_there_are_no_lessons(): void
    {
        $course = Course::create([
            'title' => 'German A1', 'slug' => 'german-a1', 'published' => true,
            'body' => '<p>Course body</p>',
        ]);

        $this->get("/courses/{$course->slug}")
            ->assertOk()
            ->assertDontSee('Lesson breakdown');
    }

    public function test_enrolment_form_preselects_the_course_being_viewed(): void
    {
        $course = Course::create([
            'title' => 'German A1', 'slug' => 'german-a1', 'published' => true,
            'body' => '<p>Course body</p>',
        ]);

        $form = Form::create(['name' => 'Enrol now', 'slug' => 'enrolment', 'published' => true]);

        FormField::create(['form_id' => $form->id, 'key' => 'course', 'label' => 'Course',
            'type' => 'select', 'required' => true, 'options_source' => 'courses', 'ordering' => 10]);

        $this->get("/courses/{$course->slug}")
            ->assertOk()
            ->assertSee('Enrol now')
            ->assertSee('<option value="German A1" selected>', false);
    }

    public function test_payment_info_setting_renders_on_course_pages_only_when_filled(): void
    {
        $course = Course::create([
            'title' => 'German A1', 'slug' => 'german-a1', 'published' => true,
            'body' => '<p>Course body</p>',
        ]);

        $this->get("/courses/{$course->slug}")->assertOk()->assertDontSee('How to pay');

        Setting::create(['key' => 'payment_info', 'label' => 'How to pay', 'type' => 'richtext',
            'group' => 'payments', 'value' => '<p>Send the fee via bKash to 01700-000000.</p>']);
        Setting::flushCache();

        $this->get("/courses/{$course->slug}")
            ->assertOk()
            ->assertSee('How to pay')
            ->assertSee('Send the fee via bKash to 01700-000000.');
    }

    public function test_empty_published_page_says_so_instead_of_rendering_blank(): void
    {
        $page = Page::create(['title' => 'Team', 'slug' => 'team', 'body' => '', 'published' => true]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertSee('no content yet', false);
    }

    public function test_legacy_index_php_url_is_redirected(): void
    {
        Page::create(['title' => 'About', 'slug' => 'about', 'body' => '<p>x</p>', 'published' => true]);

        Redirect::create(['source' => '/index.php/about', 'target' => '/about', 'status_code' => 301]);

        $this->get('/index.php/about')
            ->assertStatus(301)
            ->assertRedirect(url('/about'));
    }

    public function test_legacy_component_url_ignores_itemid(): void
    {
        Page::create(['title' => 'About', 'slug' => 'about', 'body' => '<p>x</p>', 'published' => true]);

        Redirect::create([
            'source' => '/index.php?option=com_content&view=article&id=1',
            'target' => '/about',
            'status_code' => 301,
        ]);

        // Itemid is stripped during normalization, so this still matches.
        $this->get('/index.php?option=com_content&view=article&id=1&Itemid=109')
            ->assertStatus(301)
            ->assertRedirect(url('/about'));
    }

    public function test_redirect_does_not_shadow_a_live_route(): void
    {
        $page = Page::create(['title' => 'About', 'slug' => 'about', 'body' => '<p>Live</p>', 'published' => true]);

        Redirect::create(['source' => '/about', 'target' => '/', 'status_code' => 301]);

        $this->get('/about')->assertOk()->assertSee('Live', false);
    }

    public function test_menu_item_without_a_destination_is_not_a_link(): void
    {
        $menu = Menu::create(['name' => 'Main', 'slug' => 'mainmenu']);

        MenuItem::create([
            'menu_id' => $menu->id, 'title' => 'Training',
            'type' => 'none', 'published' => true, 'ordering' => 10,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Training')
            ->assertDontSee('href="#"', false);
    }

    public function test_form_stores_a_submission_and_notifies(): void
    {
        Notification::fake();

        $form = Form::create([
            'name' => 'Contact', 'slug' => 'contact', 'published' => true,
            'store_submissions' => true, 'notify_emails' => ['office@example.com'],
        ]);

        FormField::create(['form_id' => $form->id, 'key' => 'name', 'label' => 'Name',
            'type' => 'text', 'required' => true, 'ordering' => 10]);
        FormField::create(['form_id' => $form->id, 'key' => 'email', 'label' => 'Email',
            'type' => 'email', 'required' => true, 'ordering' => 20]);

        $this->post(route('form.submit', $form), [
            'data' => ['name' => 'Test User', 'email' => 'test@example.com'],
        ])->assertRedirect();

        $this->assertSame(1, FormSubmission::count());
        $this->assertSame('Test User', FormSubmission::first()->data['name']);
    }

    public function test_form_rejects_invalid_input(): void
    {
        $form = Form::create(['name' => 'Contact', 'slug' => 'contact', 'published' => true, 'store_submissions' => true]);

        FormField::create(['form_id' => $form->id, 'key' => 'email', 'label' => 'Email',
            'type' => 'email', 'required' => true, 'ordering' => 10]);

        $this->post(route('form.submit', $form), ['data' => ['email' => 'not-an-email']])
            ->assertSessionHasErrors('data.email');

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_honeypot_silently_discards_a_bot_submission(): void
    {
        $form = Form::create(['name' => 'Contact', 'slug' => 'contact', 'published' => true, 'store_submissions' => true]);

        $this->post(route('form.submit', $form), ['website' => 'http://spam.example'])
            ->assertRedirect();

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_settings_drive_site_identity(): void
    {
        Setting::create(['key' => 'site_name', 'label' => 'Site name', 'type' => 'text',
            'value' => 'Test Institute']);
        Setting::create(['key' => 'copyright', 'label' => 'Copyright', 'type' => 'text',
            'value' => '© {year} Test Institute.']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Test Institute')
            ->assertSee('© '.now()->year.' Test Institute.', false);
    }

    public function test_placeholder_social_links_are_not_rendered(): void
    {
        // Helix stored all five socials as "#". They import as NULL so the
        // footer shows no dead icons.
        Setting::create(['key' => 'social_facebook', 'label' => 'Facebook', 'type' => 'url', 'value' => null]);

        $this->get('/')->assertOk()->assertDontSee('>facebook<', false);
    }

    public function test_sitemap_lists_only_published_content(): void
    {
        Page::create(['title' => 'Live', 'slug' => 'live', 'body' => '<p>x</p>', 'published' => true]);
        Page::create(['title' => 'Draft', 'slug' => 'draft', 'body' => '<p>x</p>', 'published' => false]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/live')
            ->assertDontSee('/draft');
    }

    public function test_courses_page_groups_by_language_with_featured_language_first(): void
    {
        Course::create(['title' => 'Arabic One', 'slug' => 'arabic-one', 'language' => 'Arabic', 'published' => true]);
        Course::create(['title' => 'German A1', 'slug' => 'german-a1', 'language' => 'German', 'published' => true, 'featured' => true]);
        Course::create(['title' => 'Hidden', 'slug' => 'hidden', 'language' => 'Korean', 'published' => false]);

        $this->get('/courses')
            ->assertOk()
            ->assertSeeInOrder(['id="lang-german"', 'German A1', 'id="lang-arabic"', 'Arabic One'], false)
            ->assertDontSee('id="lang-korean"', false);
    }

    public function test_course_spotlight_shows_chosen_courses_in_order_and_hides_drafts(): void
    {
        $a2 = Course::create(['title' => 'German A2', 'slug' => 'german-a2', 'language' => 'German', 'cefr_levels' => ['A2'], 'published' => true]);
        $a1 = Course::create(['title' => 'German A1', 'slug' => 'german-a1', 'language' => 'German', 'cefr_levels' => ['A1'], 'published' => true]);
        $draft = Course::create(['title' => 'German B1', 'slug' => 'german-b1', 'language' => 'German', 'cefr_levels' => ['B1'], 'published' => false]);

        Block::create([
            'group' => 'home', 'type' => 'course_spotlight', 'title' => 'German Programme', 'published' => true,
            'data' => ['course_ids' => [(string) $a1->id, (string) $a2->id, (string) $draft->id]],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('German Programme')
            ->assertSeeInOrder(['/courses/german-a1', '/courses/german-a2'], false)
            ->assertDontSee('/courses/german-b1', false);
    }

    public function test_language_grid_and_gallery_blocks_render(): void
    {
        Course::create(['title' => 'Korean One', 'slug' => 'korean-one', 'language' => 'Korean', 'published' => true]);

        Block::create(['group' => 'home', 'type' => 'language_grid', 'title' => 'Languages we teach', 'published' => true]);
        Block::create([
            'group' => 'home', 'type' => 'gallery', 'title' => 'Campus', 'published' => true,
            'data' => ['images' => [['image' => 'media/gallery/lab.webp', 'caption' => 'Language lab', 'alt' => 'A language lab']]],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('/courses#lang-korean', false)
            ->assertSee('/storage/media/gallery/lab.webp', false)
            ->assertSee('Language lab');
    }
}
