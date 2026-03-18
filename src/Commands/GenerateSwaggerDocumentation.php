<?php

namespace AutoSwagger\Docs\Commands;

use AutoSwagger\Docs\Exceptions\InvalidAuthenticationFlow;
use AutoSwagger\Docs\Exceptions\InvalidDefinitionException;
use AutoSwagger\Docs\Helpers\ConfigHelper;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use AutoSwagger\Docs\Formatter;
use AutoSwagger\Docs\Generator;
use Illuminate\Support\Facades\File;
use AutoSwagger\Docs\Exceptions\ExtensionNotLoaded;
use AutoSwagger\Docs\Exceptions\InvalidFormatException;

/**
 * Class GenerateSwaggerDocumentation
 * @package AutoSwagger\Docs\Commands
 */
class GenerateSwaggerDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'swagger:generate
                            {--format=json : Output format: json or yaml}
                            {--f|filter=   : Override the route prefix filter (e.g. /api or /v2/api)}
                            {--page=       : Generate docs for a specific page; omit to generate all pages}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generate Swagger/OpenAPI documentation for one or all configured pages';

    /**
     * @throws InvalidFormatException
     * @throws ExtensionNotLoaded
     * @throws InvalidAuthenticationFlow
     * @throws InvalidDefinitionException
     */
    public function handle(): void
    {
        $format     = $this->option('format');
        $filter     = $this->option('filter') ?: null;
        $pageOption = $this->option('page');

        $allPages = config('swagger.pages', []);

        if (empty($allPages)) {
            $this->error('No swagger pages are configured in config/swagger.php.');
            return;
        }

        $pagesToGenerate = $pageOption
            ? [$pageOption => Arr::get($allPages, $pageOption)]
            : $allPages;

        if ($pageOption && !isset($allPages[$pageOption])) {
            $this->error("Swagger page '{$pageOption}' is not defined in config/swagger.php.");
            return;
        }

        $storagePath = config('swagger.storage');
        File::isDirectory($storagePath) or File::makeDirectory($storagePath, 0777, true, true);

        foreach (array_keys($pagesToGenerate) as $name) {
            $pageConfig  = ConfigHelper::resolvePageConfig($name);
            $routeFilter = $filter ?: Arr::get($pageConfig, 'api_base_path');

            $repo          = new ConfigRepository(['swagger' => $pageConfig]);
            $documentation = (new Generator($repo, $routeFilter))->generate();
            $formattedDocs = (new Formatter($documentation))->setFormat($format)->format();

            $file = $storagePath . DIRECTORY_SEPARATOR . $name . '.' . $format;
            file_put_contents($file, $formattedDocs);

            $this->info("Page '{$name}' → {$file}");
        }
    }
}
