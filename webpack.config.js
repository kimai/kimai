var Encore = require('@symfony/webpack-encore');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/build/')

    // empty the outputPath directory before each build
    .cleanupOutputBeforeBuild()

    // add debug data in development
    .enableSourceMaps(!Encore.isProduction())

    // uncomment to create hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // generate only two files: app.js and app.css
    .addEntry('app', './assets/app.js')

    // enable sass/scss parser
    .enableSassLoader()

    // show OS notifications when builds finish/fail
    .enableBuildNotifications()

    // load jquery as Kimai and AdminLTE rely on it
    .autoProvidejQuery()

    // see https://symfony.com/doc/current/frontend/encore/bootstrap.html
    .enableSassLoader(function(sassOptions) {}, {
        resolveUrlLoader: false
    })

    // add hash after file name
    .configureFilenames({
        js: '[name].js?[chunkhash]',
        css: '[name].css?[contenthash]',
        images: 'images/[name].[ext]?[hash:8]',
        fonts: 'fonts/[name].[ext]?[hash:8]'
    })
;

module.exports = Encore.getWebpackConfig();
