/*
 * This file is part of the Kimai time-tracking app.
 *
 * Main JS application file for Kimai 2. This file should be included in all pages.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] Wrapper class for loading Kimai app in browser script scope
 */

import KimaiLoader from "./KimaiLoader";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], function () {
            return (root.KimaiWebLoader = factory());
        });
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.KimaiWebLoader = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {

    class KimaiWebLoader extends KimaiLoader {
    }

    return KimaiWebLoader;

}));
