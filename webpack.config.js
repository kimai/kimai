var Encore = require('@symfony/webpack-encore');
var webpack = require('webpack');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')

    // the public path is ONLY used to reference fonts from within CSS
    // you can ignore the warning message triggered by webpack encore

    // FIXME

    //.setPublicPath('./')
    .setPublicPath('/build/')

    // used by the asset twig helper to find the correct entry from manifest.json (prefix for each manifest entry)
    .setManifestKeyPrefix('build/')

    // empty the outputPath directory before each build
    .cleanupOutputBeforeBuild()

    // add debug data in development
    .enableSourceMaps(!Encore.isProduction())

    // uncomment to create hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // generate only two files: app.js and app.css
    .addEntry('app', './assets/app.js')
    .addEntry('chart', './assets/chart.js')
    .addEntry('calendar', './assets/calendar.js')

    .enableSingleRuntimeChunk()
    .splitEntryChunks()

    // show OS notifications when builds finish/fail
    .enableBuildNotifications()

    // load jquery, as Kimai and AdminLTE rely on it
    .autoProvidejQuery()

    // enable sass/scss parser
    // see https://symfony.com/doc/current/frontend/encore/bootstrap.html
    .enableSassLoader(function(sassOptions) {}, {
        resolveUrlLoader: false
    })

    // prevent that all moment locales will be included
    .addPlugin(new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/))

    // add hash after file name
    .configureFilenames({
        js: '[name].js?[contenthash]',
        css: '[name].css?[contenthash]',
        images: 'images/[name].[ext]?[hash:8]',
        fonts: 'fonts/[name].[ext]?[hash:8]'
    })
;

module.exports = Encore.getWebpackConfig();
