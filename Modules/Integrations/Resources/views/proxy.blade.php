<!doctype html>
<html lang="en">
<head>
    <title>TRANSBAZA PROXY API</title>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/3.23.6/swagger-ui.css" rel="stylesheet">
</head>
<body>
<div id="swagger-ui"></div>
<script src="//unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
<script>
    const ui = SwaggerUIBundle({
        url: "https://api-test.trans-baza.ru/api/proxy.json",
        dom_id: '#swagger-ui',
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIBundle.SwaggerUIStandalonePreset
        ],
    })
</script>
</body>
</html>