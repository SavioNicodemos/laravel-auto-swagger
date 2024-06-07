<?php

namespace AutoSwagger\Docs\Http\Controllers;

use AutoSwagger\Docs\Generator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use AutoSwagger\Docs\Formatter;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Config\Repository;
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

    /**
     * Configuration repository
     * @var Repository
     */
    protected Repository $configuration;

    /**
     * @var UIDriversService
     */
    protected UIDriversService $uiDriversService;

    /**
     * SwaggerController constructor.
     * @param Repository $configuration
     */
    public function __construct(Repository $configuration, UIDriversService $uiDriversService)
    {
        $this->configuration = $configuration;
        $this->uiDriversService = $uiDriversService;
    }

    /**
     * Return documentation content
     * @param Request $request
     * @return Response
     * @throws ExtensionNotLoaded|InvalidFormatException|InvalidAuthenticationFlow
     */
    public function documentation(Request $request): Response
    {
        $documentation = swagger_resolve_documentation_file_path();
        if (strlen($documentation) === 0) {
            abort(404, sprintf('Please generate documentation first, then access this page'));
        }
        if (config('swagger.generated', false)) {
            $documentation = (new Generator($this->configuration))->generate();
            return ResponseFacade::make((new Formatter($documentation))->setFormat('json')->format(), 200, [
                'Content-Type' => 'application/json',
            ]);
        }
        $content = File::get($documentation);
        $yaml = Str::endsWith('yaml', pathinfo($documentation, PATHINFO_EXTENSION));
        if ($yaml) {
            return ResponseFacade::make($content, 200, [
                'Content-Type' => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }
        return ResponseFacade::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Render Swagger UI page
     * @param Request $request
     * @return Response
     */
    public function api(Request $request): Response
    {
        $url = config('app.url');

        if (!Str::startsWith($url, 'http://') && !Str::startsWith($url, 'https://')) {
            $schema = swagger_is_connection_secure() ? 'https://' : 'http://';
            $url = $schema . $url;
        }

        return ResponseFacade::make(view(
            $this->uiDriversService->getViewPath(),
            [
                'secure'            =>  swagger_is_connection_secure(),
                'urlToDocs'         =>  $url . config('swagger.path', '/documentation') . '/content'
            ]
        ), 200);
    }
}
