// ------------------- INLINED ADMIN-LTE DEFINITIONS -------------------
// require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/admin-lte');
// this was replaced to save around 300kb by:
// - removing moment locales which are not used
// - removing fullcalendar locales which are not used
// - removing icheck which is not used
// - removing jquery-ui which is not used

const $ = require('jquery');
global.$ = global.jQuery = $;

require('bootstrap-sass');
require('jquery-slimscroll');

require('select2');
require('select2/dist/js/i18n/ar');
require('select2/dist/js/i18n/cs');
require('select2/dist/js/i18n/da');
require('select2/dist/js/i18n/de');
require('select2/dist/js/i18n/es');
require('select2/dist/js/i18n/eu');
require('select2/dist/js/i18n/fr');
require('select2/dist/js/i18n/hu');
require('select2/dist/js/i18n/it');
require('select2/dist/js/i18n/ja');
require('select2/dist/js/i18n/ko');
require('select2/dist/js/i18n/nl');
require('select2/dist/js/i18n/pl');
require('select2/dist/js/i18n/pt-BR');
require('select2/dist/js/i18n/ru');
require('select2/dist/js/i18n/sk');
require('select2/dist/js/i18n/sv');
require('select2/dist/js/i18n/tr');
require('select2/dist/js/i18n/zh-CN');

const Moment = require('moment');
global.moment = Moment;
require('moment/locale/ar');
require('moment/locale/cs');
require('moment/locale/da');
require('moment/locale/de');
require('moment/locale/de-ch');
require('moment/locale/eo');
require('moment/locale/es');
require('moment/locale/eu');
require('moment/locale/fr');
require('moment/locale/hu');
require('moment/locale/it');
require('moment/locale/ja');
require('moment/locale/ko');
require('moment/locale/nl');
require('moment/locale/pl');
require('moment/locale/pt-br');
require('moment/locale/ru');
require('moment/locale/sk');
require('moment/locale/sv');
require('moment/locale/tr');
require('moment/locale/zh-cn');

require('daterangepicker');

const Sortable = require('sortablejs/Sortable.min');
global.Sortable = Sortable;

// ------ AdminLTE framework ------
require('./sass/bootstrap.scss');
require('./sass/fontawesome.scss');
require('admin-lte/dist/css/AdminLTE.min.css');
require('admin-lte/dist/css/skins/_all-skins.css');
require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/admin-lte-extensions.scss');

global.$.AdminLTE = {};
global.$.AdminLTE.options = {};
require('admin-lte/dist/js/adminlte.min');
// ------------------- INLINED ADMIN-LTE DEFINITIONS -------------------
// ---------------------------------------------------------------------

require('./sass/app.scss');

// ------ Kimai itself ------
require('./js/KimaiWebLoader.js');
global.KimaiPaginatedBoxWidget = require('./js/widgets/KimaiPaginatedBoxWidget').default;
global.KimaiReloadPageWidget = require('./js/widgets/KimaiReloadPageWidget').default;

// ------ Autocomplete for tags only ------
require('jquery-ui/ui/widgets/autocomplete');
