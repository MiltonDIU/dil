<?php

namespace App\Console\Commands\Legacy;

use App\Models\Form;
use App\Models\FormField;
use App\Models\Setting;

/**
 * Seed the dynamic form builder.
 *
 * IMPORTANT: nothing here is migrated. The legacy Joomla site had NO form
 * extension — no Visforms/RSForm/Convert Forms tables exist, `#__fields` is
 * empty, and there are zero submissions. The legacy "Contact" page was a
 * static address block.
 *
 * These two forms are therefore a NEW capability, seeded so the builder ships
 * useful rather than empty. Editors can change every field without a deploy.
 */
class SeedForms extends LegacyCommand
{
    protected $signature = 'legacy:forms {--force : Reset fields even if an editor has changed them}';

    protected $description = 'Seed the contact, course-enquiry and enrolment forms (no legacy source exists)';

    public function handle(): int
    {
        foreach ($this->definitions() as $definition) {
            $fields = $definition['fields'];
            unset($definition['fields']);

            $form = Form::updateOrCreate(['slug' => $definition['slug']], $definition);

            $form->wasRecentlyCreated ? $this->created++ : $this->updated++;

            if (! $form->wasRecentlyCreated && ! $this->option('force')) {
                $this->skip("kept editor's fields for '{$form->slug}'");

                continue;
            }

            foreach ($fields as $ordering => $field) {
                FormField::updateOrCreate(
                    ['form_id' => $form->id, 'key' => $field['key']],
                    $field + ['ordering' => ($ordering + 1) * 10, 'published' => true],
                );
            }
        }

        return $this->report('Forms', count($this->definitions()), Form::count());
    }

    /** @return array<int, array<string, mixed>> */
    private function definitions(): array
    {
        $notify = array_filter([Setting::get('contact_email') ?: 'diloffice@daffodilvarsity.edu.bd']);

        return [
            [
                'name' => 'Contact',
                'slug' => 'contact',
                'description' => 'General enquiries.',
                'submit_label' => 'Send message',
                'success_message' => 'Thank you — we have received your message and will reply shortly.',
                'notify_emails' => $notify,
                'store_submissions' => true,
                'published' => true,
                'fields' => [
                    ['key' => 'name', 'label' => 'Your name', 'type' => 'text',
                        'required' => true, 'width' => 'half',
                        'validation' => ['min' => 2, 'max' => 120]],
                    ['key' => 'email', 'label' => 'Email address', 'type' => 'email',
                        'required' => true, 'width' => 'half'],
                    ['key' => 'phone', 'label' => 'Phone', 'type' => 'tel',
                        'required' => false, 'width' => 'half',
                        'validation' => ['max' => 30]],
                    ['key' => 'subject', 'label' => 'Subject', 'type' => 'text',
                        'required' => false, 'width' => 'half',
                        'validation' => ['max' => 200]],
                    ['key' => 'message', 'label' => 'Message', 'type' => 'textarea',
                        'required' => true, 'width' => 'full',
                        'validation' => ['min' => 10, 'max' => 5000]],
                ],
            ],
            [
                'name' => 'Course Enquiry',
                'slug' => 'course-enquiry',
                'description' => 'Enquire about a specific course.',
                'submit_label' => 'Send enquiry',
                'success_message' => 'Thank you — our admissions team will contact you about this course.',
                'notify_emails' => $notify,
                'store_submissions' => true,
                'published' => true,
                'fields' => [
                    ['key' => 'name', 'label' => 'Your name', 'type' => 'text',
                        'required' => true, 'width' => 'half',
                        'validation' => ['min' => 2, 'max' => 120]],
                    ['key' => 'email', 'label' => 'Email address', 'type' => 'email',
                        'required' => true, 'width' => 'half'],
                    ['key' => 'phone', 'label' => 'Phone', 'type' => 'tel',
                        'required' => true, 'width' => 'half',
                        'validation' => ['max' => 30]],
                    // Options come live from the courses table, so publishing a
                    // new course updates this dropdown with no form edit.
                    ['key' => 'course', 'label' => 'Course', 'type' => 'select',
                        'required' => true, 'width' => 'half',
                        'options_source' => 'courses'],
                    ['key' => 'message', 'label' => 'Your question', 'type' => 'textarea',
                        'required' => false, 'width' => 'full',
                        'validation' => ['max' => 3000]],
                ],
            ],
            [
                'name' => 'Enrol now',
                'slug' => 'enrolment',
                'description' => 'Fill in your details and our admissions office will contact you to complete your enrolment.',
                'submit_label' => 'Submit enrolment',
                'success_message' => 'Thank you — your enrolment request has been received. Our admissions office will contact you shortly.',
                'notify_emails' => $notify,
                'store_submissions' => true,
                'published' => true,
                'fields' => [
                    ['key' => 'name', 'label' => 'Full name', 'type' => 'text',
                        'required' => true, 'width' => 'half',
                        'validation' => ['min' => 2, 'max' => 120]],
                    ['key' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date',
                        'required' => true, 'width' => 'half'],
                    ['key' => 'email', 'label' => 'Email address', 'type' => 'email',
                        'required' => true, 'width' => 'half'],
                    ['key' => 'phone', 'label' => 'Phone', 'type' => 'tel',
                        'required' => true, 'width' => 'half',
                        'validation' => ['max' => 30]],
                    ['key' => 'education', 'label' => 'Educational qualification', 'type' => 'text',
                        'required' => false, 'width' => 'half',
                        'validation' => ['max' => 200]],
                    ['key' => 'address', 'label' => 'Present address', 'type' => 'text',
                        'required' => false, 'width' => 'half',
                        'validation' => ['max' => 300]],
                    // Options come live from the courses table, and the course
                    // page pre-selects the course being viewed.
                    ['key' => 'course', 'label' => 'Course', 'type' => 'select',
                        'required' => true, 'width' => 'half',
                        'options_source' => 'courses'],
                    // Plain strings: resolvedOptions() uses each as both value
                    // and label, so submissions read naturally in the admin.
                    ['key' => 'mode', 'label' => 'Mode of learning', 'type' => 'select',
                        'required' => true, 'width' => 'half',
                        'options' => ['Offline (on campus)', 'Online']],
                ],
            ],
        ];
    }
}
