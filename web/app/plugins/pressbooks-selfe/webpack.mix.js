let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.setPublicPath('dist')
	.combine(
		[
			'node_modules/country-region-selector/dist/crs.js',
			'node_modules/select2/dist/js/select2.js',
			'assets/scripts/main.js'
		],
		'dist/scripts/main.js'
	)
	.sass('assets/styles/main.scss', 'dist/styles/')
	.copyDirectory('assets/fonts', 'dist/fonts')
	.copyDirectory('assets/images', 'dist/images')
	.version()
  .options({
    processCssUrls: false
  });
