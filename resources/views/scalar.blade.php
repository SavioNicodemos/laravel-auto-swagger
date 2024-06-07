<!doctype html>
<html>

<head>
    <title>{{ config('swagger.title') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

</head>

<body>
    <script id="api-reference" data-url="{!! $urlToDocs !!}"></script>

    <script>
        document.getElementById('api-reference').dataset.configuration =
            "{{ json_encode(config('swagger.ui.configs.scalar')) }}"
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
    <style>
        a.darklight-reference-promo {
            display: none;
        }
    </style>
</body>

</html>