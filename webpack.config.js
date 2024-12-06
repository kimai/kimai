// Hint: if something doesn't work as expected: yarn upgrade --latest

const Encore = require('@symfony/webpack-encore');

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

    .splitEntryChunks()
    .configureSplitChunks((splitChunks) => {
        splitChunks.chunks = 'async';
    })
    .enableSingleRuntimeChunk()

    .configureBabel((config) => {
        config.sourceType = 'unambiguous';
        config.plugins.push('@babel/plugin-syntax-dynamic-import');
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
        config.targets = {};
        config.modules = false;
    })

    .enableIntegrityHashes(Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSourceMaps(!Encore.isProduction())
    .enableSassLoader((options) => {})

    .configureCssMinimizerPlugin((options) => {
        options.minimizerOptions = {
            preset: ['default', { discardComments: { removeAll: true } }],
        }
    })

    // compress javascript in production build
    .configureTerserPlugin((options) => {
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
