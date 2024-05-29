<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=1024, initial-scale=1">

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />

    <script src="/mosaico/rs/mosaico-libs-and-tinymce.min.js?v=0.17.4"></script>
    <script src="/mosaico/rs/mosaico.min.js?v=0.17.4"></script>
    <script>
        $(function() {
            if (!Mosaico.isCompatible()) {
                alert('Update your browser!');
                return;
            }
            // var basePath = window.location.href.substr(0, window.location.href.lastIndexOf('/')).substr(window.location.href.indexOf('/','https://'.length));
            var basePath = window.location.href + '/actions';
            if (basePath.lastIndexOf('#') > 0) basePath = basePath.substr(0, basePath.lastIndexOf('#'));
            if (basePath.lastIndexOf('?') > 0) basePath = basePath.substr(0, basePath.lastIndexOf('?'));
            if (basePath.lastIndexOf('/') > 0) basePath = basePath.substr(0, basePath.lastIndexOf('/'));
            var plugins;
            // A basic plugin that expose the "viewModel" object as a global variable.
            // plugins = [function(vm) {window.viewModel = vm;}];
            var ok = Mosaico.init({
                imgProcessorBackend: basePath+'/img',
                emailProcessorBackend: basePath+'/dl',
                titleToken: "MOSAICO Responsive Email Designer",
                fileuploadConfig: {
                    url: basePath+'/upload',
                    // messages??
                },

            }, plugins);
            if (!ok) {
                console.log("Missing initialization hash, redirecting to main entrypoint");
                //document.location = ".";
            }
        });
    </script>

    <link rel="stylesheet" href="/mosaico/rs/mosaico-libs-and-tinymce.min.css?v=0.17.4" />
    <link rel="stylesheet" href="/mosaico/rs/mosaico-material.min.css?v=0.17.4" />
</head>
<body class="mo-standalone">


</body>
</html>
