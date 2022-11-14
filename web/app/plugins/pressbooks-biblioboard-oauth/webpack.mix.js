let mix = require( 'laravel-mix' );

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

mix
	.setPublicPath( 'dist' )
	.scripts('assets/scripts/login-form.js', 'dist/scripts/login-form.js')
	.sass( 'assets/styles/oauth.scss', 'dist/styles/' )
	.sass( 'assets/styles/login-form.scss', 'dist/styles/' )
	.copyDirectory( 'assets/fonts', 'dist/fonts' )
	.copyDirectory( 'assets/images', 'dist/images' )
	.version()
	.options( { processCssUrls: false } );
