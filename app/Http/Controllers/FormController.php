<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Notifications\FormSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One renderer/handler for every form. Validation is derived entirely from
 * `form_fields`, so an editor adding a field in Filament gets working
 * validation with no deploy.
 */
class FormController extends Controller
{
    public function submit(Request $request, Form $form): RedirectResponse
    {
        abort_unless($form->published, 404);

        // Honeypot: a real browser leaves this empty. Pretend success so bots
        // do not learn they were caught.
        if (filled($request->input('website'))) {
            return back()->with('form_success.'.$form->slug, $form->success_message);
        }

        $key = 'form:'.$form->id.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors(['form' => 'Too many submissions. Please try again in a minute.'])
                ->withInput();
        }

        RateLimiter::hit($key, 60);

        $form->load('publishedFields');

        $validated = $request->validate(
            $form->validationRules(),
            $form->validationMessages(),
            $form->validationAttributes(),
        );

        $data = $this->collect($form, $validated['data'] ?? [], $request);

        $submission = null;

        if ($form->store_submissions) {
            $submission = FormSubmission::create([
                'form_id' => $form->id,
                'data' => $data,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'referer' => substr((string) $request->headers->get('referer'), 0, 255) ?: null,
            ]);
        }

        $this->notify($form, $data, $submission);

        return back()
            ->with('form_success.'.$form->slug, $form->success_message ?: 'Thank you — your message has been sent.')
            ->withFragment('form-'.$form->slug);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function collect(Form $form, array $input, Request $request): array
    {
        $data = [];

        foreach ($form->publishedFields as $field) {
            if ($field->type === 'heading') {
                continue;
            }

            if ($field->type === 'file') {
                $file = $request->file('data.'.$field->key);

                // File uploads follow the same disk-relative convention as
                // every other upload in the app.
                $data[$field->key] = $file
                    ? $file->store('media/form-uploads/'.$form->slug, 'public')
                    : null;

                continue;
            }

            $data[$field->key] = $input[$field->key] ?? null;
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * A failed notification must never lose a submission that is already
     * stored, so this is deliberately non-fatal.
     */
    private function notify(Form $form, array $data, ?FormSubmission $submission): void
    {
        $recipients = array_filter((array) $form->notify_emails);

        if ($recipients === []) {
            return;
        }

        try {
            Notification::route('mail', $recipients)
                ->notify(new FormSubmitted($form, $data, $submission));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
