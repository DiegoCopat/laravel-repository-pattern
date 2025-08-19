<?php
// src/Commands/InstallCommand.php

namespace DiegoCopat\RepositoryPattern\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'diegocopat:install 
                            {--only-command : Installa solo il comando make:module}
                            {--with-examples : Crea moduli di esempio}';

    protected $description = 'Installa Repository Pattern by Diego Copat';

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║       Repository Pattern Installer by Diego Copat        ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($this->option('only-command')) {
            $this->installOnlyCommand();
        } else {
            $this->fullInstall();
        }

        $this->info('');
        $this->info('✅ Installazione completata!');
        $this->info('');
        $this->info('📚 Uso:');
        $this->info('   php artisan make:module NomeModulo --all');
        $this->info('');
        $this->info('🔗 GitHub: https://github.com/diegocopat/laravel-repository-pattern');
        $this->info('');
    }

    protected function installOnlyCommand()
    {
        $this->info('📦 Installazione comando make:module...');

        // Copia solo il comando
        $commandPath = app_path('Console/Commands/MakeModuleCommand.php');
        
        if (!file_exists($commandPath)) {
            if (!is_dir(app_path('Console/Commands'))) {
                mkdir(app_path('Console/Commands'), 0755, true);
            }
            
            copy(
                __DIR__.'/../Commands/MakeModuleCommand.php',
                $commandPath
            );
            
            $this->info('✓ Comando installato!');
        } else {
            $this->warn('⚠ Il comando esiste già');
        }

        // Pubblica gli stubs
        $this->call('vendor:publish', [
            '--tag' => 'diegocopat-stubs',
            '--force' => true
        ]);
    }

    protected function fullInstall()
    {
        $this->info('📦 Installazione completa Repository Pattern...');

        // 1. Crea directory
        $this->createDirectories();

        // 2. Crea e registra RepositoryServiceProvider
        $this->setupServiceProvider();

        // 3. Installa comando
        $this->installOnlyCommand();

        // 4. Crea esempi se richiesto
        if ($this->option('with-examples')) {
            $this->createExamples();
        }
    }

    protected function createDirectories()
    {
        $directories = [
            app_path('Repositories'),
            app_path('Services'),
            app_path('Http/Requests'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
                $this->info("   ✓ Creata directory: {$directory}");
            }
        }
    }

    protected function setupServiceProvider()
    {
        // Crea il provider se non esiste
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        
        if (!file_exists($providerPath)) {
            $stub = file_get_contents(__DIR__.'/../Stubs/service-provider.stub');
            file_put_contents($providerPath, $stub);
            $this->info('   ✓ RepositoryServiceProvider creato');
        }

        // Registra nel bootstrap/providers.php o config/app.php
        $this->registerProvider();
    }

    protected function registerProvider()
    {
        $providersFile = base_path('bootstrap/providers.php');
        
        if (file_exists($providersFile)) {
            // Laravel 11+
            $content = file_get_contents($providersFile);
            
            if (!str_contains($content, 'RepositoryServiceProvider')) {
                $content = str_replace(
                    'return [',
                    "return [\n    App\Providers\RepositoryServiceProvider::class,",
                    $content
                );
                
                file_put_contents($providersFile, $content);
                $this->info('   ✓ Provider registrato in bootstrap/providers.php');
            }
        } else {
            $this->warn('   ⚠ Registra manualmente RepositoryServiceProvider');
        }
    }

    protected function createExamples()
    {
        $this->info('📚 Creazione moduli di esempio...');

        $this->call('make:module', [
            'name' => 'Item',
            '--all' => true
        ]);

        $this->info('   ✓ Modulo Item creato come esempio');
    }
}