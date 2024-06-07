<?php
    function booleanString($value) {
        return $value ? 'true' : 'false';
    }

    $configs = config('swagger.ui.configs.rapidoc');

    $showHeader = booleanString($configs['show-header'] ?? true);
    $allowLoadFile = booleanString($configs['allow-spec-file-load'] ?? true);
    $allowDownload = booleanString($configs['allow-spec-file-download'] ?? true);
    $allowLoadUrl = booleanString($configs['allow-spec-url-load'] ?? true);
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ config('swagger.title') }}</title>
    <script type="module" src="https://unpkg.com/rapidoc/dist/rapidoc-min.js"></script>
</head>

<body>
    <rapi-doc 
        spec-url="{!! $urlToDocs !!}" 
        theme="{!! $configs['theme'] ?? '' !!}" 
        render-style="{!! $configs['render-style'] ?? '' !!}"
        schema-style="{!! $configs['schema-style'] ?? '' !!}"
        header-color="{!! $configs['colors']['header'] ?? '' !!}"
        primary-color="{!! $configs['colors']['primary'] ?? '' !!}"
        show-header="{!! $showHeader !!}"
        allow-spec-file-load="{!! $allowLoadFile !!}"
        allow-spec-file-download="{!! $allowDownload !!}"
        allow-spec-url-load="{!! $allowLoadUrl !!}"
    >
        @if ($configs['logo-url'] ?? '' )
            <img slot="nav-logo" src="{!! $configs['logo-url'] ?? '' !!}" />
        @endif
    </rapi-doc>
</body>

</html>