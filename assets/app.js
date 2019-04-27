// ------------------- INLINED ADMIN-LTE DEFINITIONS -------------------
//require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/admin-lte');

const $ = require('jquery');
global.$ = global.jQuery = $;

require('jquery-ui');
require('bootstrap-sass');
require('jquery-slimscroll');
require('bootstrap-select');

const Moment = require('moment');
global.moment = Moment;
require('moment/locale/de');
require('moment/locale/it');
require('moment/locale/fr');
require('moment/locale/es');
require('moment/locale/ru');
require('moment/locale/ar');
require('moment/locale/hu');
require('moment/locale/pt-br');

require('daterangepicker');

// ------ AdminLTE framework ------
require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/admin-lte.scss');
require('admin-lte/dist/css/AdminLTE.min.css');
require('admin-lte/dist/css/skins/_all-skins.css');
require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/admin-lte-extensions.scss');

global.$.AdminLTE = {};
global.$.AdminLTE.options = {};
require('admin-lte/dist/js/adminlte.min');

// ------ Theme itself ------
require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/default_avatar.png');

// ------------------- INLINED ADMIN-LTE DEFINITIONS -------------------
// ---------------------------------------------------------------------

require('./sass/app.scss');

// ------ Kimai itself ------
require('./js/kimai.js');
require('./js/datatable.js');
require('./js/toolbar.js');
require('./images/default_avatar.png');
require('./images/signature.png');
