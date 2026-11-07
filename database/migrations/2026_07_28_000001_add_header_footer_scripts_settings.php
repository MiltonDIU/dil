<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Lets an editor drop arbitrary tracking/verification snippets (Search
     * Console, Meta Pixel, Tag Manager, etc.) into the page without a code
     * change — rendered raw, right before `</head>` and `</body>`.
     *
     * A plain data migration, not `legacy:settings`, because this setting has
     * no Joomla equivalent and `legacy:*` commands are dev-only (see
     * CLAUDE.md) — this is the path that actually reaches production.
     */
    public function up(): void
    {
        $now = now();

        foreach ($this->rows() as $row) {
            // Insert only if missing: safe to run again, and never clobbers a
            // value an editor has already set.
            if (! DB::table('settings')->where('key', $row['key'])->exists()) {
                DB::table('settings')->insert($row + [
                    'value' => null,
                    'options' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_column($this->rows(), 'key'))->delete();
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        return [
            [
                'key' => 'header_scripts',
                'group' => 'seo',
                'label' => 'Header scripts',
                'type' => 'html',
                'description' => 'Raw HTML/JS, e.g. Search Console verification or Tag Manager. Inserted just before </head>.',
                'ordering' => 50,
            ],
            [
                'key' => 'footer_scripts',
                'group' => 'seo',
                'label' => 'Footer scripts',
                'type' => 'html',
                'description' => 'Raw HTML/JS, e.g. chat widgets or pixels. Inserted just before </body>.',
                'ordering' => 60,
            ],
        ];
    }
};
