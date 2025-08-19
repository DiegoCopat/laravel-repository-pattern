<?php
// src/Commands/MakeModuleCommand.php

namespace DiegoCopat\RepositoryPattern\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module 
                            {name : Il nome del modulo (es. Product)}
                            {--all : Genera tutti i componenti}
                            {--controller : Genera solo i controller}
                            {--request : Genera solo i request}
                            {--service : Genera solo i service}
                            {--repository : Genera solo i repository}
                            {--model : Genera anche il model}
                            {--migration : Genera anche la migration}
                            {--api : Genera anche versioni API/Axios}
                            {--force : Sovrascrive file esistenti}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un modulo completo con Repository Pattern e Service Layer (by Diego Copat)';

    /**
     * Nome del modulo
     */
    protected $moduleName;

    /**
     * Nome del modulo in plurale
     */
    protected $pluralName;

    /**
     * Nome del modulo in lowercase
     */
    protected $lowerName;

    /**
     * Path degli stubs
     */
    protected $stubsPath;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->moduleName = ucfirst($this->argument('name'));
        $this->pluralName = Str::plural($this->moduleName);
        $this->lowerName = strtolower($this->moduleName);

        // Determina il path degli stubs (package o locale)
        $this->stubsPath = $this->getStubsPath();

        $this->displayHeader();

        if ($this->option('all')) {
            $this->generateModel();
            $this->generateMigration();
            $this->generateControllers();
            $this->generateRequests();
            $this->generateServices();
            $this->generateRepositories();
            $this->updateServiceProvider();
            $this->generateRoutes();
        } else {
            if ($this->option('model')) $this->generateModel();
            if ($this->option('migration')) $this->generateMigration();
            if ($this->option('controller')) $this->generateControllers();
            if ($this->option('request')) $this->generateRequests();
            if ($this->option('service')) $this->generateServices();
            if ($this->option('repository')) $this->generateRepositories();
        }

        $this->displaySuccess();
        $this->showNextSteps();
    }

    /**
     * Mostra header stilizzato
     */
    protected function displayHeader()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║     Repository Pattern Module Generator by Diego Copat   ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->info("🚀 Creazione modulo: {$this->moduleName}");
        $this->info('');
    }

    /**
     * Mostra messaggio di successo
     */
    protected function displaySuccess()
    {
        $this->info('');
        $this->info("✅ Modulo {$this->moduleName} creato con successo!");
        $this->info('');
    }

    /**
     * Ottieni il path degli stubs
     */
    protected function getStubsPath()
    {
        // Prima controlla se ci sono stubs personalizzati nel progetto
        if (File::exists(base_path('stubs/diegocopat'))) {
            return base_path('stubs/diegocopat');
        }
        
        // Altrimenti usa gli stubs del package
        return __DIR__ . '/../Stubs';
    }

    /**
     * Genera il Model
     */
    protected function generateModel()
    {
        $modelPath = app_path("Models/{$this->moduleName}.php");
        
        if (File::exists($modelPath) && !$this->option('force')) {
            $this->warn("   ⚠ Model {$this->moduleName} già esistente, skip...");
            return;
        }

        $stub = $this->getStub('model');
        $content = $this->replaceVariables($stub);
        
        File::ensureDirectoryExists(dirname($modelPath));
        File::put($modelPath, $content);
        
        $this->info("   ✓ Model creato: {$modelPath}");
    }

    /**
     * Genera la Migration
     */
    protected function generateMigration()
    {
        $tableName = Str::snake(Str::plural($this->moduleName));
        $migrationName = "create_{$tableName}_table";
        
        $this->call('make:migration', [
            'name' => $migrationName,
            '--create' => $tableName
        ]);
    }

    /**
     * Genera i Controller (tradizionale e API)
     */
    protected function generateControllers()
    {
        // Controller tradizionale
        $controllerPath = app_path("Http/Controllers/{$this->moduleName}Controller.php");
        $this->generateFile($controllerPath, 'controller', '   ✓ Controller creato');

        // Controller API se richiesto
        if ($this->option('api')) {
            $apiControllerPath = app_path("Http/Controllers/Api/{$this->moduleName}Controller.php");
            $this->generateFile($apiControllerPath, 'controller-api', '   ✓ API Controller creato');
        }
    }

    /**
     * Genera i Request
     */
    protected function generateRequests()
    {
        $requests = ['Store', 'Update', 'Index', 'Show', 'Destroy'];
        
        foreach ($requests as $request) {
            // Request tradizionale
            $requestPath = app_path("Http/Requests/{$this->moduleName}/{$request}Request.php");
            $this->generateFile($requestPath, 'request', "   ✓ Request {$request} creato", [
                'requestType' => $request
            ]);

            // Request API se richiesto
            if ($this->option('api')) {
                $apiRequestPath = app_path("Http/Requests/Api/{$this->moduleName}/{$request}Request.php");
                $this->generateFile($apiRequestPath, 'request-api', "   ✓ API Request {$request} creato", [
                    'requestType' => $request
                ]);
            }
        }
    }

    /**
     * Genera i Service
     */
    protected function generateServices()
    {
        // Service tradizionale
        $servicePath = app_path("Services/{$this->moduleName}/{$this->moduleName}Service.php");
        $this->generateFile($servicePath, 'service', '   ✓ Service creato');

        // Service API se richiesto
        if ($this->option('api')) {
            $apiServicePath = app_path("Services/Api/{$this->moduleName}/{$this->moduleName}Service.php");
            $this->generateFile($apiServicePath, 'service-api', '   ✓ API Service creato');
        }
    }

    /**
     * Genera i Repository
     */
    protected function generateRepositories()
    {
        // Repository Interface
        $interfacePath = app_path("Repositories/{$this->moduleName}/{$this->moduleName}RepositoryInterface.php");
        $this->generateFile($interfacePath, 'repository-interface', '   ✓ Repository Interface creato');

        // Repository Implementation
        $repositoryPath = app_path("Repositories/{$this->moduleName}/{$this->moduleName}Repository.php");
        $this->generateFile($repositoryPath, 'repository', '   ✓ Repository creato');

        // API versions se richiesto
        if ($this->option('api')) {
            $apiInterfacePath = app_path("Repositories/Api/{$this->moduleName}/{$this->moduleName}RepositoryInterface.php");
            $this->generateFile($apiInterfacePath, 'repository-interface-api', '   ✓ API Repository Interface creato');

            $apiRepositoryPath = app_path("Repositories/Api/{$this->moduleName}/{$this->moduleName}Repository.php");
            $this->generateFile($apiRepositoryPath, 'repository-api', '   ✓ API Repository creato');
        }
    }

    /**
     * Aggiorna il Service Provider
     */
    protected function updateServiceProvider()
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        
        if (!File::exists($providerPath)) {
            $this->generateServiceProvider();
        }

        $this->info('   ✓ Service Provider configurato');
        $this->warn('   ⚠ Ricordati di registrare i binding nel RepositoryServiceProvider!');
        
        // Mostra esempio di binding
        $this->info('');
        $this->line('   Aggiungi questo binding nel metodo register() del RepositoryServiceProvider:');
        $this->info('');
        $this->line("   \$this->app->bind(");
        $this->line("       \\App\\Repositories\\{$this->moduleName}\\{$this->moduleName}RepositoryInterface::class,");
        $this->line("       \\App\\Repositories\\{$this->moduleName}\\{$this->moduleName}Repository::class");
        $this->line("   );");
    }

    /**
     * Genera il Service Provider se non esiste
     */
    protected function generateServiceProvider()
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        $stub = $this->getStub('service-provider');
        $content = $this->replaceVariables($stub);
        
        File::ensureDirectoryExists(dirname($providerPath));
        File::put($providerPath, $content);
        
        $this->info('   ✓ RepositoryServiceProvider creato');
        $this->warn('   ⚠ Ricordati di registrare il provider in bootstrap/providers.php!');
    }

    /**
     * Genera le route di esempio
     */
    protected function generateRoutes()
    {
        $routesPath = base_path("routes/{$this->lowerName}.php");
        $stub = $this->getStub('routes');
        $content = $this->replaceVariables($stub);
        
        File::put($routesPath, $content);
        $this->info("   ✓ File routes creato: {$routesPath}");
        $this->warn('   ⚠ Ricordati di includere le route in routes/web.php o routes/api.php!');
    }

    /**
     * Genera un file da stub
     */
    protected function generateFile($path, $stubName, $successMessage, $additionalReplacements = [])
    {
        if (File::exists($path) && !$this->option('force')) {
            $this->warn("   ⚠ File già esistente: {$path}");
            return;
        }

        $stub = $this->getStub($stubName);
        $content = $this->replaceVariables($stub, $additionalReplacements);
        
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        
        $this->info($successMessage);
    }

    /**
     * Ottieni contenuto dello stub
     */
    protected function getStub($name)
    {
        $stubFile = $this->stubsPath . "/{$name}.stub";
        
        // Se lo stub non esiste nel path custom, usa quello inline
        if (!File::exists($stubFile)) {
            return $this->getInlineStub($name);
        }
        
        return File::get($stubFile);
    }

    /**
     * Sostituisci le variabili nello stub
     */
    protected function replaceVariables($stub, $additionalReplacements = [])
    {
        $replacements = array_merge([
            '{{moduleName}}' => $this->moduleName,
            '{{moduleNameLower}}' => $this->lowerName,
            '{{moduleNamePlural}}' => $this->pluralName,
            '{{moduleNamePluralLower}}' => strtolower($this->pluralName),
            '{{namespace}}' => 'App',
        ], $additionalReplacements);

        foreach ($replacements as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }

    /**
     * Mostra i prossimi passi
     */
    protected function showNextSteps()
    {
        $this->info('📝 Prossimi passi:');
        $this->info('');
        $this->line("   1. Registra i binding nel RepositoryServiceProvider");
        $this->line("   2. Registra il RepositoryServiceProvider in bootstrap/providers.php (se nuovo)");
        $this->line("   3. Includi le route in routes/web.php o routes/api.php");
        $this->line("   4. Esegui le migration: php artisan migrate");
        $this->line("   5. Personalizza i file generati secondo le tue esigenze");
        $this->info('');
        $this->info('💡 Per maggiori informazioni:');
        $this->line('   GitHub: https://github.com/diegocopat/laravel-repository-pattern');
        $this->info('');
    }

    /**
     * Ottieni stub inline (quando non ci sono file stub esterni)
     */
    protected function getInlineStub($name)
    {
        $stubs = [
            'model' => '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {{moduleName}} extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        \'name\',
        \'description\',
        \'status\',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        \'created_at\' => \'datetime\',
        \'updated_at\' => \'datetime\',
        \'deleted_at\' => \'datetime\',
    ];
}',

            'controller' => '<?php

namespace App\Http\Controllers;

use App\Services\{{moduleName}}\{{moduleName}}Service;
use App\Http\Requests\{{moduleName}}\StoreRequest;
use App\Http\Requests\{{moduleName}}\UpdateRequest;
use App\Http\Requests\{{moduleName}}\IndexRequest;
use Illuminate\Http\Request;

class {{moduleName}}Controller extends Controller
{
    protected ${{moduleNameLower}}Service;

    public function __construct({{moduleName}}Service ${{moduleNameLower}}Service)
    {
        $this->{{moduleNameLower}}Service = ${{moduleNameLower}}Service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request)
    {
        $data = $this->{{moduleNameLower}}Service->getAll($request->validated());
        
        return view(\'{{moduleNameLower}}.index\', compact(\'data\'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view(\'{{moduleNameLower}}.create\');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        ${{moduleNameLower}} = $this->{{moduleNameLower}}Service->create($request->validated());
        
        return redirect()
            ->route(\'{{moduleNameLower}}.index\')
            ->with(\'success\', \'{{moduleName}} creato con successo!\');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        ${{moduleNameLower}} = $this->{{moduleNameLower}}Service->find($id);
        
        return view(\'{{moduleNameLower}}.show\', compact(\'{{moduleNameLower}}\'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        ${{moduleNameLower}} = $this->{{moduleNameLower}}Service->find($id);
        
        return view(\'{{moduleNameLower}}.edit\', compact(\'{{moduleNameLower}}\'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, $id)
    {
        ${{moduleNameLower}} = $this->{{moduleNameLower}}Service->update($id, $request->validated());
        
        return redirect()
            ->route(\'{{moduleNameLower}}.index\')
            ->with(\'success\', \'{{moduleName}} aggiornato con successo!\');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->{{moduleNameLower}}Service->delete($id);
        
        return redirect()
            ->route(\'{{moduleNameLower}}.index\')
            ->with(\'success\', \'{{moduleName}} eliminato con successo!\');
    }
}',

            'service' => '<?php

namespace App\Services\{{moduleName}};

use App\Repositories\{{moduleName}}\{{moduleName}}RepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class {{moduleName}}Service
{
    protected $repository;

    public function __construct({{moduleName}}RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Ottieni tutti i {{moduleNameLower}}
     */
    public function getAll(array $filters = [])
    {
        try {
            return $this->repository->getAllWithFilters($filters);
        } catch (Exception $e) {
            Log::error(\'Errore nel recupero dei {{moduleNameLower}}: \' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Trova un {{moduleNameLower}} per ID
     */
    public function find($id)
    {
        try {
            return $this->repository->find($id);
        } catch (Exception $e) {
            Log::error(\'Errore nel recupero del {{moduleNameLower}}: \' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crea un nuovo {{moduleNameLower}}
     */
    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Logica business aggiuntiva qui
            $data[\'created_by\'] = auth()->id();
            
            ${{moduleNameLower}} = $this->repository->create($data);
            
            DB::commit();
            
            return ${{moduleNameLower}};
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(\'Errore nella creazione del {{moduleNameLower}}: \' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Aggiorna un {{moduleNameLower}}
     */
    public function update($id, array $data)
    {
        DB::beginTransaction();
        
        try {
            // Logica business aggiuntiva qui
            $data[\'updated_by\'] = auth()->id();
            
            ${{moduleNameLower}} = $this->repository->update($id, $data);
            
            DB::commit();
            
            return ${{moduleNameLower}};
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(\'Errore nell\\\'aggiornamento del {{moduleNameLower}}: \' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un {{moduleNameLower}}
     */
    public function delete($id)
    {
        DB::beginTransaction();
        
        try {
            $result = $this->repository->delete($id);
            
            DB::commit();
            
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(\'Errore nell\\\'eliminazione del {{moduleNameLower}}: \' . $e->getMessage());
            throw $e;
        }
    }
}',

            'repository-interface' => '<?php

namespace App\Repositories\{{moduleName}};

interface {{moduleName}}RepositoryInterface
{
    /**
     * Ottieni tutti i record
     */
    public function all();

    /**
     * Ottieni tutti i record con filtri
     */
    public function getAllWithFilters(array $filters);

    /**
     * Trova un record per ID
     */
    public function find($id);

    /**
     * Crea un nuovo record
     */
    public function create(array $data);

    /**
     * Aggiorna un record
     */
    public function update($id, array $data);

    /**
     * Elimina un record
     */
    public function delete($id);

    /**
     * Conta i record
     */
    public function count();

    /**
     * Verifica se esiste un record
     */
    public function exists($id);
}',

            'repository' => '<?php

namespace App\Repositories\{{moduleName}};

use App\Models\{{moduleName}};
use Illuminate\Database\Eloquent\ModelNotFoundException;

class {{moduleName}}Repository implements {{moduleName}}RepositoryInterface
{
    protected $model;

    public function __construct({{moduleName}} $model)
    {
        $this->model = $model;
    }

    /**
     * Ottieni tutti i record
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * Ottieni tutti i record con filtri
     */
    public function getAllWithFilters(array $filters)
    {
        $query = $this->model->query();

        // Ricerca
        if (!empty($filters[\'search\'])) {
            $query->where(function ($q) use ($filters) {
                $q->where(\'name\', \'like\', \'%\' . $filters[\'search\'] . \'%\')
                  ->orWhere(\'description\', \'like\', \'%\' . $filters[\'search\'] . \'%\');
            });
        }

        // Ordinamento
        $sortBy = $filters[\'sort_by\'] ?? \'created_at\';
        $sortOrder = $filters[\'sort_order\'] ?? \'desc\';
        $query->orderBy($sortBy, $sortOrder);

        // Paginazione
        $perPage = $filters[\'per_page\'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Trova un record per ID
     */
    public function find($id)
    {
        ${{moduleNameLower}} = $this->model->find($id);
        
        if (!${{moduleNameLower}}) {
            throw new ModelNotFoundException(\'{{moduleName}} non trovato\');
        }
        
        return ${{moduleNameLower}};
    }

    /**
     * Crea un nuovo record
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Aggiorna un record
     */
    public function update($id, array $data)
    {
        ${{moduleNameLower}} = $this->find($id);
        ${{moduleNameLower}}->update($data);
        
        return ${{moduleNameLower}}->fresh();
    }

    /**
     * Elimina un record
     */
    public function delete($id)
    {
        ${{moduleNameLower}} = $this->find($id);
        
        return ${{moduleNameLower}}->delete();
    }

    /**
     * Conta i record
     */
    public function count()
    {
        return $this->model->count();
    }

    /**
     * Verifica se esiste un record
     */
    public function exists($id)
    {
        return $this->model->where(\'id\', $id)->exists();
    }
}',

            'request' => '<?php

namespace App\Http\Requests\{{moduleName}};

use Illuminate\Foundation\Http\FormRequest;

class {{requestType}}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Aggiungi le tue regole di validazione qui
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Messaggi personalizzati
        ];
    }
}',

            'service-provider' => '<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // I binding dei repository verranno aggiunti qui
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
}',

            'routes' => '<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{{moduleName}}Controller;

/**
 * {{moduleName}} Routes
 */
Route::prefix(\'{{moduleNamePluralLower}}\')->name(\'{{moduleNameLower}}.\')->group(function () {
    Route::get(\'/\', [{{moduleName}}Controller::class, \'index\'])->name(\'index\');
    Route::get(\'/create\', [{{moduleName}}Controller::class, \'create\'])->name(\'create\');
    Route::post(\'/\', [{{moduleName}}Controller::class, \'store\'])->name(\'store\');
    Route::get(\'/{id}\', [{{moduleName}}Controller::class, \'show\'])->name(\'show\');
    Route::get(\'/{id}/edit\', [{{moduleName}}Controller::class, \'edit\'])->name(\'edit\');
    Route::put(\'/{id}\', [{{moduleName}}Controller::class, \'update\'])->name(\'update\');
    Route::delete(\'/{id}\', [{{moduleName}}Controller::class, \'destroy\'])->name(\'destroy\');
});',
        ];

        return $stubs[$name] ?? '<?php // Stub not found';
    }
}