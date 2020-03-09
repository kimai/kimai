// Hint: if something doesn't work as expected: yarn upgrade --latest

var Encore = require('@symfony/webpack-encore');
var webpack = require('webpack');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('build/')
    .setManifestKeyPrefix('build/')
    .cleanupOutputBeforeBuild()

    .addEntry('app', './assets/app.js')
    .addEntry('invoice', './assets/invoice.js')
    .addEntry('chart', './assets/chart.js')
    .addEntry('calendar', './assets/calendar.js')
    .copyFiles({ from: './assets/images', to: 'images/[path][name].[ext]' })

    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .enableIntegrityHashes()
    .enableVersioning(Encore.isProduction())

    .enableSourceMaps(!Encore.isProduction())
    .enableBuildNotifications()

    .enableSassLoader()
    .autoProvidejQuery()

    // prevent that moment locales will be included which we don't need
    .addPlugin(new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/))

    .configureBabel(null, {
        useBuiltIns: 'usage',
        corejs: 3,
    })

    .configureOptimizeCssPlugin((options) => {
        options.cssProcessorPluginOptions = {
            preset: ['default', { discardComments: { removeAll: true } }],
        }
    })

    .configureTerserPlugin((options) => {
        options.cache = true;
        options.terserOptions = {
            output: {
                comments: false
            }
        }
    })
;

var config = Encore.getWebpackConfig();

// this is a hack based on https://github.com/symfony/webpack-encore/issues/88
// to rewrite the font url() in CSS to be relative.
// if you encounter any problems ... please let me know!
config.module.rules[3].options.publicPath = './';

module.exports = config;
