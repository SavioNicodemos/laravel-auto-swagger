<?php

namespace AutoSwagger\Docs\Http\Controllers;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Helpers\ConfigHelper;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use AutoSwagger\Docs\Formatter;
use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controller as BaseController;
use AutoSwagger\Docs\Exceptions\ExtensionNotLoaded;
use Illuminate\Support\Facades\Response as ResponseFacade;
use AutoSwagger\Docs\Exceptions\InvalidFormatException;
use AutoSwagger\Docs\Exceptions\InvalidAuthenticationFlow;
use AutoSwagger\Docs\Services\UIDriversService;

/**
 * Class SwaggerController
 * @package AutoSwagger\Docs\Http\Controllers
 */
class SwaggerController extends BaseController
{
    protected UIDriversService $uiDriversService;

    public function __construct(UIDriversService $uiDriversService)
    {
        $this->uiDriversService = $uiDriversService;
    }

    /**
     * Return the OpenAPI spec for the requested page.
     *
     * @throws ExtensionNotLoaded|InvalidFormatException|InvalidAuthenticationFlow
     */
    public function documentation(Request $request): Response
    {
        $page       = $request->route('_swagger_page', 'default');
        $pageConfig = ConfigHelper::resolvePageConfig($page);

        if ($pageConfig['generated'] ?? false) {
            $repo          = new ConfigRepository(['swagger' => $pageConfig]);
            $documentation = (new Generator($repo))->generate();

            return ResponseFacade::make(
                (new Formatter($documentation))->setFormat('json')->format(),
                200,
                ['Content-Type' => 'application/json']
            );
        }

        $filePath = swagger_resolve_documentation_file_path($page);

        if (strlen($filePath) === 0) {
            abort(404, "Please generate documentation for page '{$page}' first, then access this page.");
        }

        $content = File::get($filePath);
        $isYaml  = Str::endsWith(pathinfo($filePath, PATHINFO_EXTENSION), 'yaml');

        if ($isYaml) {
            return ResponseFacade::make($content, 200, [
                'Content-Type'        => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }

        return ResponseFacade::make($content, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Render the Swagger UI page for the requested page.
     */
    public function api(Request $request): Response
    {
        $page       = $request->route('_swagger_page', 'default');
        $pageConfig = ConfigHelper::resolvePageConfig($page);

        $driver   = $pageConfig['ui_driver'] ?? 'swagger-ui';
        $pagePath = $pageConfig['path'] ?? '/docs';
        $title    = $pageConfig['title'] ?? config('app.name', 'API Documentation');

        $appUrl = config('app.url', '');
        if (!Str::startsWith($appUrl, 'http://') && !Str::startsWith($appUrl, 'https://')) {
            $schema = swagger_is_connection_secure() ? 'https://' : 'http://';
            $appUrl = $schema . $appUrl;
        }

        return ResponseFacade::make(view(
            $this->uiDriversService->getViewPath($driver),
            [
                'secure'    => swagger_is_connection_secure(),
                'urlToDocs' => $appUrl . $pagePath . '/content',
                'title'     => $title,
            ]
        ), 200);
    }
}
