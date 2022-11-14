let path = require( 'path' );
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

const inc = 'inc';
const assets = 'assets';
const dist = 'dist';

mix.setPublicPath( 'dist' );
mix.setResourceRoot( '../' );

// BrowserSync
mix.browserSync( {
	host:  'localhost',
	proxy: 'https://pressbooks.test/',
	port:  3300,
	files: [ '*.php', `${inc}/**/*.php`, `${dist}/**/*.css`, `${dist}/**/*.js` ],
} );

// Sass
mix.sass( `${assets}/styles/whitelabel.scss`, `${dist}/styles/whitelabel.css` );

// Scripts
mix.js( `${assets}/scripts/whitelabel.js`, `${dist}/scripts` );

// Assets
mix
	.copy( `${assets}/fonts`, `${dist}/fonts`, false )
	.copy( `${assets}/images`, `${dist}/images`, false );

// Options
mix.options( { processCssUrls: false } );

// Source maps when not in production.
if ( ! mix.inProduction() ) {
	mix.sourceMaps();
}

// Hash and version files in production.
if ( mix.inProduction() ) {
	mix.version();
}
