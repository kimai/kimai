require('../vendor/kevinpapst/adminlte-bundle/Resources/assets/admin-lte');

require('./sass/app.scss');

require('fullcalendar');
require('fullcalendar/dist/gcal.min');
require('fullcalendar/dist/locale-all');
require('fullcalendar/dist/fullcalendar.min.css');

// ------ for charts ------
require('chart.js/dist/Chart.min');

// ------ Kimai itself ------
require('./js/kimai.js');
require('./js/datatable.js');
require('./js/toolbar.js');
require('./images/default_avatar.png');
require('./images/signature.png');

// ------ Autocomplete ------
require('jquery-ui/ui/widgets/autocomplete');