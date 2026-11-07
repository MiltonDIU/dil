<?php

namespace App\Notifications;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FormSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public Form $form,
        public array $data,
        public ?FormSubmission $submission = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_short_name') ?: config('app.name');

        $mail = (new MailMessage)
            ->subject("[{$site}] New {$this->form->name} submission")
            ->greeting("New {$this->form->name} submission");

        $labels = $this->form->fields->pluck('label', 'key')->all();

        foreach ($this->data as $key => $value) {
            $label = $labels[$key] ?? $key;
            $mail->line('**'.$label.':** '.(is_array($value) ? implode(', ', $value) : $value));
        }

        if ($this->submission) {
            $mail->action('View in admin', url('/admin/form-submissions/'.$this->submission->id));
        }

        return $mail;
    }
}
