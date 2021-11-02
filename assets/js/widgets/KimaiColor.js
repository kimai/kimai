/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiColor: handle colors
 */

export default class KimaiColor {

    static calculateContrastColor(hexcolor)
    {
        if (hexcolor.slice(0, 1) === '#') {
            hexcolor = hexcolor.slice(1);
        }

        if (hexcolor.length === 3) {
            hexcolor = hexcolor.split('').map(function (hex) { return hex + hex; }).join('');
        }

        let r = parseInt(hexcolor.substr(0,2),16);
        let g = parseInt(hexcolor.substr(2,2),16);
        let b = parseInt(hexcolor.substr(4,2),16);
        let yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;

        return (yiq >= 128) ? '#000000' : '#ffffff';
    }

}
