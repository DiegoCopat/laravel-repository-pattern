<?php
// src/Providers/RepositoryPatternServiceProvider.php

namespace DiegoCopat\RepositoryPattern\Providers;

use Illuminate\Support\ServiceProvider;
use DiegoCopat\RepositoryPattern\Commands\MakeModuleCommand;
use DiegoCopat\RepositoryPattern\Commands\InstallCommand;
use DiegoCopat\RepositoryPattern\Commands\SetupProjectCommand;

class RepositoryPatternServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/repository-pattern.php', 
            'repository-pattern'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Registra i comandi
            $this->commands([
                MakeModuleCommand::class,
                InstallCommand::class,
                SetupProjectCommand::class,
            ]);

            // Pubblica configurazione
            $this->publishes([
                __DIR__.'/../../config/repository-pattern.php' => config_path('repository-pattern.php'),
            ], 'diegocopat-config');

            // Pubblica stubs
            $this->publishes([
                __DIR__.'/../Stubs' => base_path('stubs/diegocopat'),
            ], 'diegocopat-stubs');
        }
    }
}