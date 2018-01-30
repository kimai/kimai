// create global $ and jQuery variables
const $ = require('jquery');
global.$ = global.jQuery = $;

// build stylesheets
require('../css/vendor.scss');
require('../../vendor/almasaeed2010/adminlte/dist/css/AdminLTE.min.css');
require('../../vendor/almasaeed2010/adminlte/dist/css/skins/_all-skins.css');
require('../css/kimai.scss');

require('jquery-ui');
require('bootstrap-sass');
require('jquery-slimscroll');

const Chart = require('../../vendor/almasaeed2010/adminlte/plugins/chartjs/Chart.min.js');
global.Chart = Chart;

global.$.AdminLTE = {};
global.$.AdminLTE.options = {};
require('../../vendor/almasaeed2010/adminlte/dist/js/app.js');

require('./kimai.js');
require('./toolbar.js');

require('../images/default_avatar.png');
