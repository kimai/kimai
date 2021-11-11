
const $ = require('jquery');
global.$ = global.jQuery = $;

const Moment = require('moment');
global.moment = Moment;
require('moment/locale/ar');
require('moment/locale/cs');
require('moment/locale/da');
require('moment/locale/de');
require('moment/locale/de-at');
require('moment/locale/de-ch');
require('moment/locale/el');
require('moment/locale/eo');
require('moment/locale/es');
require('moment/locale/eu');
require('moment/locale/fi');
require('moment/locale/fo');
require('moment/locale/fr');
require('moment/locale/he');
require('moment/locale/hu');
require('moment/locale/it');
require('moment/locale/ja');
require('moment/locale/ko');
require('moment/locale/nl');
require('moment/locale/pl');
require('moment/locale/pt');
require('moment/locale/pt-br');
require('moment/locale/ro');
require('moment/locale/ru');
require('moment/locale/sk');
require('moment/locale/sv');
require('moment/locale/tr');
require('moment/locale/vi');
require('moment/locale/zh-cn');

require('daterangepicker');

require('./sass/app.scss');

// ------ Kimai itself ------
require('./js/KimaiWebLoader.js');
global.KimaiPaginatedBoxWidget = require('./js/widgets/KimaiPaginatedBoxWidget').default;
global.KimaiReloadPageWidget = require('./js/widgets/KimaiReloadPageWidget').default;
global.KimaiCookies = require('./js/widgets/KimaiCookies').default;
global.KimaiColor = require('./js/widgets/KimaiColor').default;
global.KimaiStorage = require('./js/widgets/KimaiStorage').default;
