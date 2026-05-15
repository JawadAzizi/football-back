<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateApi extends Command
{
    protected $signature = 'make:api {name : The singular name of the model}';
    protected $description = 'Create a full API CRUD with Search/Filter traits and single commented examples';

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

        // 1. Ensure Shared Traits exist
        $this->ensureApiResponseTrait();
        $this->ensureSearchTrait();
        $this->ensureFilterTrait();

        // 2. Create Model, Request, and Controller
        $this->createModel($name, $migrationData['table'], $migrationData['fields']);
        $this->createRequest($name, $migrationData['rules']);
        $this->createController($name, $modelVariable, $migrationData);

        // 3. Add Routes to api.php
        $this->addRoute($name, $pluralName, $modelVariable);

        $this->info("Successfully created API for {$name}!");
    }

    protected function ensureApiResponseTrait()
    {
        $path = app_path('Traits/ApiResponseTrait.php');
        File::ensureDirectoryExists(app_path('Traits'));
        if (File::exists($path)) return;

        File::put($path, "<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponseTrait
{
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
}");
    }

    protected function ensureSearchTrait()
    {
        $path = app_path('Traits/ApplySearch.php');
        if (File::exists($path)) return;

        File::put($path, "<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ApplySearch
{
    public function applySearch(Builder \$query, array \$searchData, string \$operator = 'LIKE'): Builder
    {
        if (empty(\$searchData)) return \$query;

        \$query->where(function (\$q) use (\$searchData, \$operator) {
            foreach (\$searchData as \$column => \$value) {
                if (!is_null(\$value) && \$value !== '') {
                    \$finalValue = (\$operator === 'LIKE') ? \"%{\$value}%\" : \$value;
                    \$q->orWhere(\$column, \$operator, \$finalValue);
                }
            }
        });

        return \$query;
    }
}");
    }

    protected function ensureFilterTrait()
    {
        $path = app_path('Traits/ApplyFilter.php');
        if (File::exists($path)) return;

        File::put($path, "<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ApplyFilter
{
    public function applyFilter(Builder \$query, array \$filterData, string \$operator = '='): Builder
    {
        foreach (\$filterData as \$column => \$value) {
            if (!is_null(\$value) && \$value !== '') {
                \$query->where(\$column, \$operator, \$value);
            }
        }
        return \$query;
    }
}");
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
        $searchable = [];

        preg_match_all('/\$table->(\w+)\([\'"]([^\'"]+)[\'"]\)([^;]*)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = $match[1];
            $column = $match[2];
            $extra = $match[3];

            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;

            $fields[] = $column;
            if (in_array($type, ['string', 'text'])) $searchable[] = $column;

            $rule = [Str::contains($extra, 'nullable') ? 'nullable' : 'required'];
            $rule[] = match ($type) {
                'string' => 'string|max:255',
                'integer' => 'integer',
                'boolean' => 'boolean',
                'decimal' => 'numeric',
                default => 'string',
            };
            $rules[$column] = implode('|', $rule);
        }

        return ['table' => $actualTable, 'fields' => $fields, 'rules' => $rules, 'searchable' => $searchable];
    }

    protected function createModel($name, $table, $fields)
    {
        $fillable = "['" . implode("', '", $fields) . "']";
        $path = app_path("Models/{$name}.php");
        File::put($path, "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class {$name} extends Model
{
    use HasFactory;
    protected \$table = '{$table}';
    protected \$fillable = {$fillable};
}");
    }

    protected function createRequest($name, $rulesArray)
    {
        $rulesExport = "[\n";
        foreach ($rulesArray as $field => $rule) {
            $rulesExport .= "            '$field' => '$rule',\n";
        }
        $rulesExport .= "        ]";

        $path = app_path("Http/Requests/{$name}Request.php");
        File::ensureDirectoryExists(app_path("Http/Requests"));
        File::put($path, "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$name}Request extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return {$rulesExport}; }
}");
    }

    protected function createController($name, $modelVariable, $migrationData)
    {
        $table = $migrationData['table'];
        $firstField = $migrationData['fields'][0] ?? 'column';
        $firstSearch = $migrationData['searchable'][0] ?? 'column';

        $path = app_path("Http/Controllers/{$name}Controller.php");
        File::put($path, "<?php

namespace App\Http\Controllers;

use App\Models\\{$name};
use App\Http\Requests\\{$name}Request;
use App\Traits\ApiResponseTrait;
use App\Traits\ApplyFilter;
use App\Traits\ApplySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class {$name}Controller extends Controller
{
    use ApiResponseTrait, ApplyFilter, ApplySearch;

    public function index(Request \$request): JsonResponse
    {
        \$query = {$name}::query();

        // Apply Filters
        \$query = \$this->applyFilter(\$query, [
            // '{$table}.{$firstField}' => \$request->input('{$firstField}'),
        ], '=');

        // Apply Search
        \$query = \$this->applySearch(\$query, [
            // '{$table}.{$firstSearch}' => \$request->input('search'),
        ], 'LIKE');

        \$items = \$query->paginate(15)->toArray();
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
}");
    }

    protected function addRoute($name, $pluralName, $modelVariable)
    {
        $routePath = base_path('routes/api.php');
        $controllerName = "{$name}Controller";
        $importStatement = "use App\Http\Controllers\\{$controllerName};";
        $routes = "\n// {$name} API Routes\nRoute::get('{$pluralName}', [{$controllerName}::class, 'index']);\nRoute::post('{$pluralName}', [{$controllerName}::class, 'store']);\nRoute::get('{$pluralName}/{{$modelVariable}}', [{$controllerName}::class, 'show']);\nRoute::patch('{$pluralName}/{{$modelVariable}}', [{$controllerName}::class, 'update']);\nRoute::delete('{$pluralName}/{{$modelVariable}}', [{$controllerName}::class, 'destroy']);\n";

        $content = File::get($routePath);
        if (!Str::contains($content, $importStatement)) {
            $content = Str::contains($content, 'use ')
                ? Str::replaceFirst('use ', "{$importStatement}\nuse ", $content)
                : Str::replaceFirst('<?php', "<?php\n\n{$importStatement}", $content);
        }
        if (!Str::contains($content, "Route::get('{$pluralName}'")) $content .= $routes;
        File::put($routePath, $content);
    }
}
