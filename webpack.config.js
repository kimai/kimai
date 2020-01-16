// Hint: if something doesn't work as expected: yarn upgrade --latest

var Encore = require('@symfony/webpack-encore');
var webpack = require('webpack');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('build/')
    .setManifestKeyPrefix('build/')
    .cleanupOutputBeforeBuild()

    .addEntry('app', './assets/app.js')
    .addEntry('locale-ar', './assets/locale/ar.js')
    .addEntry('locale-cs', './assets/locale/cs.js')
    .addEntry('locale-da', './assets/locale/da.js')
    .addEntry('locale-de', './assets/locale/de.js')
    .addEntry('locale-de_CH', './assets/locale/de_CH.js')
    .addEntry('locale-en', './assets/locale/en.js')
    .addEntry('locale-es', './assets/locale/es.js')
    .addEntry('locale-eu', './assets/locale/eu.js')
    .addEntry('locale-fr', './assets/locale/fr.js')
    .addEntry('locale-hu', './assets/locale/hu.js')
    .addEntry('locale-it', './assets/locale/it.js')
    .addEntry('locale-ja', './assets/locale/ja.js')
    .addEntry('locale-ko', './assets/locale/ko.js')
    .addEntry('locale-nl', './assets/locale/nl.js')
    .addEntry('locale-pt_BR', './assets/locale/pt_BR.js')
    .addEntry('locale-ru', './assets/locale/ru.js')
    .addEntry('locale-sk', './assets/locale/sk.js')
    .addEntry('locale-sv', './assets/locale/sv.js')
    .addEntry('locale-tr', './assets/locale/tr.js')
    .addEntry('locale-zh_CN', './assets/locale/zh_CN.js')
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
