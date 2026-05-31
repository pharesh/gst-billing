<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateMysqlToMongodb extends Command
{
    protected $signature   = 'db:mysql-to-mongodb
                              {--mysql-host=127.0.0.1 : MySQL host}
                              {--mysql-port=3306 : MySQL port}
                              {--mysql-db=gst_billing : MySQL database name}
                              {--mysql-user=root : MySQL username}
                              {--mysql-password=root : MySQL password}
                              {--fresh : Drop existing MongoDB collections before migrating}';

    protected $description = 'Migrate all data from MySQL to MongoDB Atlas (preserves integer IDs)';

    // Migrate in this order to respect foreign key dependencies
    private array $tables = [
        'plans',
        'tenants',
        'users',
        'customers',
        'products',
        'suppliers',
        'invoices',
        'invoice_items',
        'payments',
        'credit_notes',
        'credit_note_items',
        'recurring_invoices',
        'subscriptions',
    ];

    // Columns that store JSON strings in MySQL — decode them for MongoDB
    private array $jsonColumns = [
        'bank_details', 'features', 'items',
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  MySQL → MongoDB Atlas Migration');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('');

        // Dynamically configure MySQL connection from options
        config(['database.connections.mysql_migrate' => [
            'driver'    => 'mysql',
            'host'      => $this->option('mysql-host'),
            'port'      => $this->option('mysql-port'),
            'database'  => $this->option('mysql-db'),
            'username'  => $this->option('mysql-user'),
            'password'  => $this->option('mysql-password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        // Test MySQL
        try {
            DB::connection('mysql_migrate')->getPdo();
            $this->line('  ✅ MySQL connected  (<fg=cyan>' . $this->option('mysql-host') . '/' . $this->option('mysql-db') . '</>)');
        } catch (\Throwable $e) {
            $this->error('  ❌ MySQL connection failed: ' . $e->getMessage());
            $this->info('  Tip: pass correct credentials with --mysql-host --mysql-user --mysql-password --mysql-db');
            return self::FAILURE;
        }

        // Test MongoDB — use raw driver ping command
        try {
            /** @var \MongoDB\Laravel\Connection $mongoConn */
            $mongoConn = DB::connection('mongodb');
            $mongoConn->getMongoDB()->command(['ping' => 1]);
            $this->line('  ✅ MongoDB Atlas connected');
        } catch (\Throwable $e) {
            $this->error('  ❌ MongoDB connection failed: ' . $e->getMessage());
            $this->info('  Make sure MONGODB_URI is set correctly in your .env file');
            return self::FAILURE;
        }

        $this->info('');

        foreach ($this->tables as $table) {
            $this->migrateTable($table);
        }

        $this->info('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  ✅ Migration complete! Check MongoDB Atlas.');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('');

        return self::SUCCESS;
    }

    private function migrateTable(string $table): void
    {
        $mysql = DB::connection('mysql_migrate');
        $mongo = DB::connection('mongodb');

        // Check table exists
        if (!$mysql->getSchemaBuilder()->hasTable($table)) {
            $this->line("  <fg=yellow>⏭  $table — not found in MySQL, skipping</>");
            return;
        }

        $count = $mysql->table($table)->count();

        if ($count === 0) {
            $this->line("  <fg=yellow>⏭  $table — empty, skipping</>");
            return;
        }

        // Drop existing MongoDB collection if requested
        if ($this->option('fresh')) {
            $mongo->getMongoDB()->selectCollection($table)->drop();
        }

        $bar = $this->output->createProgressBar($count);
        $bar->setFormat("  <fg=green>%-20s</> %current%/%max% [%bar%] %percent%%");
        $bar->setMessage($table);
        $bar->start();

        $mysql->table($table)->orderBy('id')->chunk(500, function ($rows) use ($table, $mongo, $bar) {
            $docs = [];

            foreach ($rows as $row) {
                $doc = (array) $row;

                // Use the MySQL integer id as MongoDB _id (preserves all relationships)
                if (array_key_exists('id', $doc)) {
                    $doc['_id'] = (int) $doc['id'];
                    unset($doc['id']);
                }

                // Decode JSON string columns into native arrays for MongoDB
                foreach ($this->jsonColumns as $col) {
                    if (isset($doc[$col]) && is_string($doc[$col]) && $doc[$col] !== '') {
                        $decoded = json_decode($doc[$col], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $doc[$col] = $decoded;
                        }
                    }
                }

                // Cast numeric foreign-key columns to int for consistent queries
                foreach ($doc as $key => $value) {
                    if (str_ends_with($key, '_id') && $value !== null) {
                        $doc[$key] = (int) $value;
                    }
                }

                $docs[] = $doc;
            }

            if (!empty($docs)) {
                $mongo->getMongoDB()->selectCollection($table)->insertMany($docs);
            }

            $bar->advance(count($docs));
        });

        $bar->finish();
        $this->newLine();
    }
}
