let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/assets/js/app.js', 'public/js')
    .babel('public/js/app.js', 'public/js/appEs5.js')
    .js('Modules/Telephony/Resources/assets/js/app.js', 'public/js/adminTelephony.js')
   /*.sass('resources/assets/sass/app.scss', 'public/css')*/
   .sass('resources/assets/sass/transbaza_template/main.scss', 'public/css/theme/style.css').version();
  /* .sass('resources/assets/sass/transbaza_template/main-adaptive.scss', 'public/css/theme/adaptive.css')*/
