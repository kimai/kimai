const Encore = require('@symfony/webpack-encore').default;

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build/')
    .setManifestKeyPrefix('build/')
    .cleanupOutputBeforeBuild()

    .addEntry('app', './assets/app.js')
    .addEntry('app-rtl', './assets/app-rtl.js')
    .addEntry('export-pdf', './assets/export-pdf.js')
    .addEntry('invoice', './assets/invoice.js')
    .addEntry('invoice-pdf', './assets/invoice-pdf.js')
    .addEntry('chart', './assets/chart.js')
    .addEntry('calendar', './assets/calendar.js')
    .addEntry('dashboard', './assets/dashboard.js')
    .addEntry('highlight', './assets/highlight.js')

    .splitEntryChunks()
    .configureSplitChunks((splitChunks) => {
        splitChunks.chunks = 'async';
    })
    .enableSingleRuntimeChunk()

    .configureBabel((config) => {
        config.sourceType = 'unambiguous';
        config.plugins.push(['babel-plugin-polyfill-corejs3', {
            method: 'usage-global',
            version: require('core-js/package.json').version,
        }]);
    })
    .configureBabelPresetEnv((config) => {
        config.targets = {};
        config.modules = false;
    })

    .enableIntegrityHashes(Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSourceMaps(!Encore.isProduction())
    .enableSassLoader((options) => {})

    .configureCssMinimizerPlugin((options, MinimizerPlugin) => {
        options.minify = MinimizerPlugin.cssnanoMinify;
        options.minimizerOptions = {
            preset: ['default', { discardComments: { removeAll: true } }],
        }
    })

    // compress javascript in production build
    .configureJsMinimizerPlugin((options) => {
        options.terserOptions = {
            compress: true,
            output: {
                comments: false,
            },
            // chart.js will fail if sources are mangled
            /*
            mangle: {
                properties: {
                    regex: /^_/,
                },
            },
            */
        }
    })
;

module.exports = Encore.getWebpackConfig();
