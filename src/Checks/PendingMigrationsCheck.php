<?php

namespace Webrek\HealthUi\Checks;

use Illuminate\Database\Migrations\Migrator;
use Webrek\HealthUi\Contracts\Check;
use Webrek\HealthUi\Result;

/**
 * Warns when migrations have been committed but not run on this environment.
 */
class PendingMigrationsCheck implements Check
{
    public function name(): string
    {
        return 'migrations';
    }

    public function run(): Result
    {
        /** @var Migrator $migrator */
        $migrator = app('migrator');

        if (! $migrator->repositoryExists()) {
            return Result::warning($this->name(), 'The migrations table does not exist.');
        }

        $paths = $migrator->paths();
        $paths[] = database_path('migrations');

        $files = $migrator->getMigrationFiles($paths);
        $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());
        $count = count($pending);

        if ($count > 0) {
            return Result::warning($this->name(), "{$count} pending migration(s).", ['pending' => $count]);
        }

        return Result::ok($this->name(), 'All migrations have run.');
    }
}
