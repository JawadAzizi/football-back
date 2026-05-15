<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateApi extends Command
{
    protected $signature = 'make:api {name : The singular name of the model}';
    protected $description = 'Create a full Smart API CRUD with single-prop Array Responses';

    public function handle()
    {
        $name = ucfirst(Str::singular($this->argument('name'))); 
        $pluralName = Str::plural(strtolower($name));           
        $searchTable = Str::snake($pluralName);                  
        $modelVariable = Str::camel($name);                     

        $this->info("Parsing migration for: {$searchTable}...");
        $migrationData = $this->parseMigration($searchTable);

        if (!$migrationData) {
            $this->error("Migration file for [{$searchTable}] not found!");
            return;
        }

        $this->ensureTraitExists();
        $this->createModel($name, $migrationData['table'], $migrationData['fields']);
        $this->createRequest($name, $migrationData['rules']);
        $this->createController($name, $modelVariable);
        $this->addRoute($name, $pluralName, $modelVariable);

        $this->info("Successfully created Smart API for {$name}!");
    }

    /**
     * Creates the Smart Trait with Single Prop Array logic
     */
    protected function ensureTraitExists()
    {
        $path = app_path('Traits/ApiResponseTrait.php');
        File::ensureDirectoryExists(app_path('Traits'));

        $template = "<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponseTrait
{
    /**
     * Smart API Response
     * @param array \$props ['data' => mixed, 'action' => string, 'message' => string, 'code' => int]
     */
    public function apiResponse(array \$props): JsonResponse
    {
        \$action  = \$props['action'] ?? 'list';
        \$data    = \$props['data'] ?? null;
        \$message = \$props['message'] ?? null;
        \$code    = \$props['code'] ?? null;

        \$defaults = [
            'create'  => ['code' => Response::HTTP_CREATED, 'msg' => 'Resource created successfully'],
            'update'  => ['code' => Response::HTTP_OK, 'msg' => 'Resource updated successfully'],
            'destroy' => ['code' => Response::HTTP_OK, 'msg' => 'Resource deleted successfully'],
            'show'    => ['code' => Response::HTTP_OK, 'msg' => 'Resource retrieved successfully'],
            'list'    => ['code' => Response::HTTP_OK, 'msg' => 'Resources listed successfully'],
        ];

        \$settings = \$defaults[\$action] ?? ['code' => Response::HTTP_OK, 'msg' => 'Success'];
        \$finalCode = \$code ?? \$settings['code'];

        return response()->json([
            'success' => \$finalCode < 400,
            'action'  => \$action,
            'message' => \$message ?? \$settings['msg'],
            'data'    => isset(\$data['data']) ? \$data['data'] : \$data,
            'meta'    => isset(\$data['current_page']) ? array_diff_key(\$data, ['data' => []]) : null
        ], \$finalCode);
    }
}";
        File::put($path, $template);
    }

    protected function parseMigration($searchTable)
    {
        $migrationDir = database_path('migrations');
        $files = File::files($migrationDir);
        $targetFile = null;

        foreach ($files as $file) {
            if (Str::contains($file->getFilename(), "create_{$searchTable}_table")) {
                $targetFile = $file;
                break;
            }
        }

        if (!$targetFile) return null;

        $content = File::get($targetFile);
        preg_match('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $tableMatch);
        $actualTable = $tableMatch[1] ?? $searchTable;

        $fields = [];
        $rules = [];

        preg_match_all('/\$table->(\w+)\([\'"]([^\'"]+)[\'"]\)([^;]*)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = $match[1];
            $column = $match[2];
            $extra = $match[3];

            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;

            $fields[] = $column;
            $rule = [Str::contains($extra, 'nullable') ? 'nullable' : 'required'];
            
            $rule[] = match ($type) {
                'string' => 'string|max:255',
                'text', 'longText' => 'string',
                'integer', 'bigInteger', 'unsignedInteger' => 'integer',
                'boolean' => 'boolean',
                'decimal', 'float', 'double' => 'numeric',
                'date', 'dateTime', 'timestamp' => 'date',
                default => 'string',
            };

            $rules[$column] = implode('|', $rule);
        }

        return ['table' => $actualTable, 'fields' => $fields, 'rules' => $rules];
    }

    protected function createModel($name, $table, $fields)
    {
        $fillable = "['" . implode("', '", $fields) . "']";
        $path = app_path("Models/{$name}.php");
        $template = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class {$name} extends Model
{
    use HasFactory;
    protected \$table = '{$table}';
    protected \$fillable = {$fillable};
}";
        File::put($path, $template);
    }

    protected function createRequest($name, $rulesArray)
    {
        $rulesExport = "[\n";
        foreach ($rulesArray as $field => $rule) {
            $rulesExport .= "            '$field' => '$rule',\n";
        }
        $rulesExport .= "        ]";

        $path = app_path("Http/Requests/{$name}Request.php");
        $template = "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$name}Request extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return {$rulesExport}; }
}";
        File::ensureDirectoryExists(app_path("Http/Requests"));
        File::put($path, $template);
    }

    protected function createController($name, $modelVariable)
    {
        $path = app_path("Http/Controllers/{$name}Controller.php");
        $template = "<?php

namespace App\Http\Controllers;

use App\Models\\{$name};
use App\Http\Requests\\{$name}Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class {$name}Controller extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        \$items = {$name}::paginate(15)->toArray();
        return \$this->apiResponse(['data' => \$items, 'action' => 'list']);
    }

    public function store({$name}Request \$request): JsonResponse
    {
        \${$modelVariable} = {$name}::create(\$request->validated());
        return \$this->apiResponse(['data' => \${$modelVariable}, 'action' => 'create']);
    }

    public function show({$name} \${$modelVariable}): JsonResponse
    {
        return \$this->apiResponse(['data' => \${$modelVariable}, 'action' => 'show']);
    }

    public function update({$name}Request \$request, {$name} \${$modelVariable}): JsonResponse
    {
        \${$modelVariable}->update(\$request->validated());
        return \$this->apiResponse(['data' => \${$modelVariable}, 'action' => 'update']);
    }

    public function destroy({$name} \${$modelVariable}): JsonResponse
    {
        \${$modelVariable}->delete();
        return \$this->apiResponse(['action' => 'destroy']);
    }
}";
        File::put($path, $template);
    }

    protected function addRoute($name, $pluralName, $modelVariable)
    {
        $routePath = base_path('routes/api.php');
        $controllerName = "{$name}Controller";
        $importStatement = "use App\Http\Controllers\\{$controllerName};";
        
        $routes = "
// {$name} API Routes
Route::get('{$pluralName}', [{$controllerName}::class, 'index']);
Route::post('{$pluralName}', [{$controllerName}::class, 'store']);
Route::get('{$pluralName}/{{$modelVariable}}', [{$controllerName}::class, 'show']);
Route::patch('{$pluralName}/{{$modelVariable}}', [{$controllerName}::class, 'update']);
Route::delete('{$pluralName}/{{$modelVariable}}', [{$controllerName}::class, 'destroy']);
";

        $content = File::get($routePath);

        if (!Str::contains($content, $importStatement)) {
            $content = Str::contains($content, 'use ') 
                ? Str::replaceFirst('use ', "{$importStatement}\nuse ", $content)
                : Str::replaceFirst('<?php', "<?php\n\n{$importStatement}", $content);
        }

        if (!Str::contains($content, "Route::get('{$pluralName}'")) {
            $content .= "\n{$routes}";
        }

        File::put($routePath, $content);
    }
}