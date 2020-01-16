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

const Moment = require('moment');
global.moment = Moment;

require('daterangepicker');

const Sortable = require('sortablejs/Sortable.min');
global.Sortable = Sortable;

// ------ AdminLTE framework ------
require('./sass/admin-lte.scss');
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

// ------ the timesheet calendar plugin ------
require('fullcalendar');
require('fullcalendar/dist/gcal.min');
require('fullcalendar/dist/fullcalendar.min.css');

// ------ charting for several screens, like dashboard and user profile ------
require('chart.js/dist/Chart.min');
