<?php
// src/Commands/SetupProjectCommand.php

namespace DiegoCopat\RepositoryPattern\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class SetupProjectCommand extends Command
{
    protected $signature = 'diegocopat:setup-project 
                            {--fresh : Installazione da zero}
                            {--teams : Installa con supporto teams}
                            {--ssr : Installa con SSR}
                            {--skip-npm : Salta installazione NPM}';

    protected $description = 'Setup completo del progetto Laravel con Jetstream, Inertia, e Repository Pattern by Diego Copat';

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║     Laravel Repository Pattern Setup by Diego Copat      ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($this->option('fresh')) {
            $this->setupFreshProject();
        } else {
            $this->setupExistingProject();
        }

        $this->info('');
        $this->info('✅ Setup completato con successo!');
        $this->info('👤 Admin User: admin@example.com');
        $this->info('🔑 Password: secret');
        $this->info('');
        $this->info('🚀 Prossimi passi:');
        $this->info('   1. php artisan migrate --seed');
        $this->info('   2. npm run dev (o npm run build per produzione)');
        $this->info('   3. php artisan serve');
        $this->info('');
        $this->info('📚 Documentazione: https://github.com/diegocopat/laravel-repository-pattern');
        $this->info('');
    }

    protected function setupFreshProject()
    {
        $this->info('🚀 Installazione completa del progetto...');

        // 1. Installa Jetstream
        $this->installJetstream();

        // 2. Installa Spatie Packages
        $this->installSpatiePackages();

        // 3. Setup Repository Pattern
        $this->setupRepositoryPattern();

        // 4. Crea Admin Seeder
        $this->createAdminSeeder();

        // 5. Setup NPM e build assets
        if (!$this->option('skip-npm')) {
            $this->setupNpm();
        }
    }

    protected function installJetstream()
    {
        $this->info('📦 Installazione Laravel Jetstream con Inertia...');

        Process::run('composer require laravel/jetstream', function (string $type, string $output) {
            $this->output->write($output);
        });

        $teams = $this->option('teams') ? '--teams' : '';
        $ssr = $this->option('ssr') ? '--ssr' : '';

        Artisan::call("jetstream:install inertia {$teams} {$ssr}", [], $this->output);

        $this->info('✓ Jetstream installato!');
    }

    protected function installSpatiePackages()
    {
        $this->info('📦 Installazione Spatie Packages...');

        // Spatie Permission
        Process::run('composer require spatie/laravel-permission', function (string $type, string $output) {
            $this->output->write($output);
        });

        Artisan::call('vendor:publish', [
            '--provider' => 'Spatie\Permission\PermissionServiceProvider'
        ]);

        // Spatie Media Library
        Process::run('composer require spatie/laravel-medialibrary', function (string $type, string $output) {
            $this->output->write($output);
        });

        Artisan::call('vendor:publish', [
            '--provider' => 'Spatie\MediaLibrary\MediaLibraryServiceProvider',
            '--tag' => 'medialibrary-migrations'
        ]);

        $this->info('✓ Spatie packages installati!');
    }

    protected function setupRepositoryPattern()
    {
        $this->info('🏗️ Setup Repository Pattern...');

        // Crea le directory
        $directories = [
            app_path('Repositories'),
            app_path('Services'),
            app_path('Providers'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
                $this->info("   Creata directory: {$directory}");
            }
        }

        // Crea RepositoryServiceProvider
        $this->createRepositoryServiceProvider();

        // Registra il provider
        $this->registerServiceProvider();

        // Copia il comando MakeModule
        $this->copyMakeModuleCommand();

        $this->info('✓ Repository Pattern configurato!');
    }

    protected function createRepositoryServiceProvider()
    {
        $stub = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // I binding verranno aggiunti qui man mano che crei moduli
        // Esempio:
        // $this->app->bind(
        //     \App\Repositories\Item\ItemRepositoryInterface::class,
        //     \App\Repositories\Item\ItemRepository::class
        // );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
PHP;

        file_put_contents(app_path('Providers/RepositoryServiceProvider.php'), $stub);
    }

    protected function registerServiceProvider()
    {
        $providersFile = base_path('bootstrap/providers.php');
        
        if (file_exists($providersFile)) {
            // Laravel 11+
            $content = file_get_contents($providersFile);
            
            if (!str_contains($content, 'RepositoryServiceProvider')) {
                $content = str_replace(
                    'App\Providers\AppServiceProvider::class,',
                    "App\Providers\AppServiceProvider::class,\n    App\Providers\RepositoryServiceProvider::class,",
                    $content
                );
                
                file_put_contents($providersFile, $content);
            }
        }
    }

    protected function copyMakeModuleCommand()
    {
        $commandPath = app_path('Console/Commands/MakeModuleCommand.php');
        
        if (!file_exists($commandPath)) {
            if (!is_dir(app_path('Console/Commands'))) {
                mkdir(app_path('Console/Commands'), 0755, true);
            }
            
            copy(
                __DIR__.'/../Commands/MakeModuleCommand.php',
                $commandPath
            );
        }
    }

    protected function createAdminSeeder()
    {
        $this->info('🌱 Creazione Admin Seeder...');

        $seeder = <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Crea ruoli
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Crea permessi
        $permissions = [
            'view-dashboard',
            'manage-users',
            'manage-roles',
            'manage-permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assegna tutti i permessi al ruolo admin
        $adminRole->syncPermissions(Permission::all());

        // Crea utente admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('secret'),
                'email_verified_at' => now(),
            ]
        );

        // Assegna ruolo admin
        $admin->assignRole('admin');

        // Se usi teams
        if (class_exists(Team::class)) {
            $team = Team::firstOrCreate(
                ['name' => 'Admin Team'],
                [
                    'user_id' => $admin->id,
                    'personal_team' => true,
                ]
            );

            $admin->current_team_id = $team->id;
            $admin->save();
        }

        $this->command->info('✓ Admin user creato: admin@example.com / secret');
    }
}
PHP;

        file_put_contents(database_path('seeders/AdminSeeder.php'), $seeder);

        // Aggiorna DatabaseSeeder
        $databaseSeeder = file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        
        if (!str_contains($databaseSeeder, 'AdminSeeder')) {
            $databaseSeeder = str_replace(
                'public function run(): void',
                "public function run(): void\n    {\n        \$this->call(AdminSeeder::class);",
                $databaseSeeder
            );
            
            // Chiudi la parentesi graffa se non c'è già
            if (!str_contains($databaseSeeder, '    }')) {
                $databaseSeeder = str_replace(
                    "{\n        \$this->call(AdminSeeder::class);",
                    "{\n        \$this->call(AdminSeeder::class);\n    }",
                    $databaseSeeder
                );
            }
            
            file_put_contents(database_path('seeders/DatabaseSeeder.php'), $databaseSeeder);
        }

        $this->info('✓ Admin Seeder creato!');
    }

    protected function setupNpm()
    {
        $this->info('📦 Installazione dipendenze NPM...');

        Process::run('npm install', function (string $type, string $output) {
            $this->output->write($output);
        });

        if ($this->option('ssr')) {
            Process::run('npm run build', function (string $type, string $output) {
                $this->output->write($output);
            });
        }

        $this->info('✓ NPM configurato!');
    }

    protected function setupExistingProject()
    {
        $this->info('🔧 Configurazione progetto esistente...');

        // Chiedi cosa installare
        if ($this->confirm('Vuoi installare Jetstream?', true)) {
            $this->installJetstream();
        }

        if ($this->confirm('Vuoi installare Spatie packages?', true)) {
            $this->installSpatiePackages();
        }

        $this->setupRepositoryPattern();

        if ($this->confirm('Vuoi creare Admin seeder?', true)) {
            $this->createAdminSeeder();
        }
    }
}