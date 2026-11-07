<?php

namespace App\Console\Commands\Legacy;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MigrateUsers extends LegacyCommand
{
    protected $signature = 'legacy:users';

    protected $description = 'Import Joomla users (bcrypt hashes are preserved)';

    public function handle(): int
    {
        $cfg = $this->cfg('users');

        // Joomla group titles, so we only grant admin to real administrators.
        $adminGroups = DB::connection(config('migration.connection'))
            ->table('user_usergroup_map as m')
            ->join('usergroups as g', 'g.id', '=', 'm.group_id')
            ->whereIn('g.title', ['Super Users', 'Administrator', 'Manager'])
            ->pluck('m.user_id')
            ->flip();

        $source = $this->legacy($cfg);
        $sourceCount = (clone $source)->count();

        foreach ($source->get() as $row) {
            $data = $this->mapRow($row, $cfg);

            if (blank($data['email'] ?? null)) {
                $this->skip('no email address');

                continue;
            }

            $data['is_admin'] = $adminGroups->has($row->id);

            /*
             * Both legacy hashes are bcrypt ($2y$, 60 chars) — verified in
             * recon — so Laravel's `hashed` cast passes them through untouched
             * and both admins keep their existing passwords. Anything else
             * would need a reset, so we refuse rather than silently break login.
             */
            if (! str_starts_with((string) $data['password'], '$2y$')) {
                $this->warn("  user {$row->username}: unsupported hash format, password cleared");
                $data['password'] = bin2hex(random_bytes(24));
            }

            $this->upsert(User::class, $cfg['source'], $row->id, $data);
        }

        return $this->report('Users', $sourceCount, User::count());
    }
}
