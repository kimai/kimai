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
    .addEntry('invoice-pdf', './assets/invoice-pdf.js')
    .addEntry('chart', './assets/chart.js')
    .addEntry('calendar', './assets/calendar.js')
    .copyFiles({ from: './assets/images', to: 'images/[path][name].[ext]' })

    .splitEntryChunks()
    .configureSplitChunks(function(splitChunks) {
        splitChunks.chunks = 'async';
    })

    // bug: empty hashes in entrypoints.json
    //.enableIntegrityHashes(Encore.isProduction())

    .enableSingleRuntimeChunk()
    .enableVersioning(Encore.isProduction())
    .enableSourceMaps(!Encore.isProduction())
    .enableBuildNotifications()

    .enableSassLoader(function(sassOptions) {}, {
        resolveUrlLoader: false
    })

    // to rewrite the font url() in CSS to be relative.
    // https://github.com/symfony/webpack-encore/issues/915#issuecomment-827556896
    .configureFontRule(
        { type: 'javascript/auto' },
        (rule) => {
            rule.loader = 'file-loader';
            rule.options = { outputPath: 'fonts', name: '[name].[hash:8].[ext]', publicPath: './fonts/' };
        }
    )

    .configureImageRule(
        { type: 'javascript/auto' },
        (rule) => {
            rule.loader = 'file-loader';
            rule.options = { outputPath: 'images', name: '[name].[hash:8].[ext]', publicPath: './images/' };
        }
    )

    .autoProvidejQuery()

    // prevent that unused moment locales will be included
    .addPlugin(new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/))

    .configureBabel(null, {
        useBuiltIns: 'usage',
        corejs: 3,
    })

    .configureCssMinimizerPlugin((options) => {
        options.minimizerOptions = {
            preset: ['default', { discardComments: { removeAll: true } }],
        }
    })
;

module.exports = Encore.getWebpackConfig();
