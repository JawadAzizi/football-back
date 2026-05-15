<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateApi extends Command
{
    protected $signature = 'make:api {name : The singular name of the model}';
    protected $description = 'Create a full API CRUD by reading migration table name, columns, and types';

    public function handle()
    {
        $name = ucfirst(Str::singular($this->argument('name'))); 
        $pluralName = Str::plural(strtolower($name));           
        $searchTable = Str::snake($pluralName);                  
        $modelVariable = Str::camel($name);                     

        $this->info("Searching for migration for: {$searchTable}...");
        $migrationData = $this->parseMigration($searchTable);

        if (!$migrationData) {
            $this->error("Migration file for [{$searchTable}] not found. Please create it first!");
            return;
        }

        $actualTableName = $migrationData['table'];

        // 1. Create Model with explicit $table and $fillable
        $this->createModel($name, $actualTableName, $migrationData['fields']);

        // 2. Create Request with Validation rules from migration types
        $this->createRequest($name, $migrationData['rules']);

        // 3. Create Controller with Pagination and Standard Response
        $this->createController($name, $modelVariable);
        
        // 4. Add Individual Routes and Top-level Import
        $this->addRoute($name, $pluralName, $modelVariable);

        $this->info("Successfully created API for {$name} (Table: {$actualTableName})!");
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
        
        // Extract the actual table name from Schema::create('table_name')
        preg_match('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $tableMatch);
        $actualTable = $tableMatch[1] ?? $searchTable;

        $fields = [];
        $rules = [];

        // Regex to extract column type and name
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

    public function rules(): array
    {
        return {$rulesExport};
    }
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class {$name}Controller extends Controller
{
    private function apiResponse(\$data, string \$message = 'Success', int \$code = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => \$code < 400,
            'message' => \$message,
            'data'    => isset(\$data['data']) ? \$data['data'] : \$data,
            'meta'    => isset(\$data['current_page']) ? array_diff_key(\$data, ['data' => []]) : null
        ], \$code);
    }

    public function index(): JsonResponse
    {
        \$items = {$name}::paginate(15)->toArray();
        return \$this->apiResponse(\$items, 'Items retrieved successfully');
    }

    public function store({$name}Request \$request): JsonResponse
    {
        \${$modelVariable} = {$name}::create(\$request->validated());
        return \$this->apiResponse(\${$modelVariable}, 'Created successfully', Response::HTTP_CREATED);
    }

    public function show({$name} \${$modelVariable}): JsonResponse
    {
        return \$this->apiResponse(\${$modelVariable}, 'Details retrieved');
    }

    public function update({$name}Request \$request, {$name} \${$modelVariable}): JsonResponse
    {
        \${$modelVariable}->update(\$request->validated());
        return \$this->apiResponse(\${$modelVariable}, 'Updated successfully');
    }

    public function destroy({$name} \${$modelVariable}): JsonResponse
    {
        \${$modelVariable}->delete();
        return \$this->apiResponse(null, 'Deleted successfully', Response::HTTP_NO_CONTENT);
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

        // 1. Add Controller Import at the top
        if (!Str::contains($content, $importStatement)) {
            if (Str::contains($content, 'use ')) {
                $content = Str::replaceFirst('use ', "{$importStatement}\nuse ", $content);
            } else {
                $content = Str::replaceFirst('<?php', "<?php\n\n{$importStatement}", $content);
            }
        }

        // 2. Add individual routes at the bottom
        if (!Str::contains($content, "Route::get('{$pluralName}'")) {
            $content .= "\n{$routes}";
        }

        File::put($routePath, $content);
    }
}