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
require('bootstrap-select');

const Moment = require('moment');
global.moment = Moment;
require('moment/locale/ar');
require('moment/locale/cs');
require('moment/locale/de');
require('moment/locale/es');
require('moment/locale/fr');
require('moment/locale/hu');
require('moment/locale/it');
require('moment/locale/ja');
require('moment/locale/ko');
require('moment/locale/pt-br');
require('moment/locale/ru');
require('moment/locale/sk');
require('moment/locale/sv');

require('daterangepicker');

// ------ AdminLTE framework ------
require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/admin-lte.scss');
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

// ------ Autocomplete for tags only ------
require('jquery-ui/ui/widgets/autocomplete');