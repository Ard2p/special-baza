const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

mix.setPublicPath('../../public').mergeManifest();

mix.js(__dirname + '/Resources/assets/js/app.js', 'js/fmsapi.js')
    .sass( __dirname + '/Resources/assets/sass/app.scss', 'css/fmsapi.css');

if (mix.inProduction()) {
    mix.version();
}