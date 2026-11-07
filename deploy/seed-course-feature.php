<?php

/*
 * One-time production seed for the lesson-breakdown + enrolment release
 * (2026-08-06). Run from the app root:
 *
 *     php artisan tinker --execute="require 'deploy/seed-course-feature.php';"
 *
 * Idempotent: rows that already exist are left completely untouched, so it is
 * safe to run twice. Deliberately NOT a legacy:* command — those never run on
 * production.
 */

use App\Models\Form;
use App\Models\FormField;
use App\Models\Setting;

$notify = array_filter([Setting::get('contact_email') ?: 'diloffice@daffodilvarsity.edu.bd']);

$form = Form::firstOrCreate(['slug' => 'enrolment'], [
    'name' => 'Enrol now',
    'description' => 'Fill in your details and our admissions office will contact you to complete your enrolment.',
    'submit_label' => 'Submit enrolment',
    'success_message' => 'Thank you — your enrolment request has been received. Our admissions office will contact you shortly.',
    'notify_emails' => $notify,
    'store_submissions' => true,
    'published' => true,
]);

if ($form->wasRecentlyCreated) {
    $fields = [
        ['key' => 'name', 'label' => 'Full name', 'type' => 'text',
            'required' => true, 'width' => 'half', 'validation' => ['min' => 2, 'max' => 120]],
        ['key' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date',
            'required' => true, 'width' => 'half'],
        ['key' => 'email', 'label' => 'Email address', 'type' => 'email',
            'required' => true, 'width' => 'half'],
        ['key' => 'phone', 'label' => 'Phone', 'type' => 'tel',
            'required' => true, 'width' => 'half', 'validation' => ['max' => 30]],
        ['key' => 'education', 'label' => 'Educational qualification', 'type' => 'text',
            'required' => false, 'width' => 'half', 'validation' => ['max' => 200]],
        ['key' => 'address', 'label' => 'Present address', 'type' => 'text',
            'required' => false, 'width' => 'half', 'validation' => ['max' => 300]],
        ['key' => 'course', 'label' => 'Course', 'type' => 'select',
            'required' => true, 'width' => 'half', 'options_source' => 'courses'],
        ['key' => 'mode', 'label' => 'Mode of learning', 'type' => 'select',
            'required' => true, 'width' => 'half', 'options' => ['Offline (on campus)', 'Online']],
    ];

    foreach ($fields as $i => $field) {
        FormField::create($field + [
            'form_id' => $form->id, 'ordering' => ($i + 1) * 10, 'published' => true,
        ]);
    }

    echo "created enrolment form with ".count($fields)." fields\n";
} else {
    echo "enrolment form already exists — skipped\n";
}

Setting::firstOrCreate(['key' => 'enrolment_form_slug'], [
    'group' => 'features', 'label' => 'Course enrolment form', 'type' => 'text',
    'ordering' => 40, 'value' => 'enrolment',
    'description' => 'The form shown at the bottom of every course page.',
]);

// value stays NULL on purpose: the section is hidden until real payment
// details are entered under Site settings -> Payments.
Setting::firstOrCreate(['key' => 'payment_info'], [
    'group' => 'payments', 'label' => 'How to pay', 'type' => 'richtext',
    'ordering' => 10, 'value' => null,
    'description' => 'Payment instructions (bKash, bank transfer, …) shown on every course page after the lesson breakdown. Leave blank to hide the section.',
]);

Setting::flushCache();

echo "settings ensured: enrolment_form_slug, payment_info\n";
echo "done\n";
