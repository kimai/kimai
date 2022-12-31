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

    /**
     * @param {string} hexcolor
     * @return {string}
     */
    static calculateContrastColor(hexcolor)
    {
        if (hexcolor.slice(0, 1) === '#') {
            hexcolor = hexcolor.slice(1);
        }

        if (hexcolor.length === 3) {
            hexcolor = hexcolor.split('').map(function (hex) { return hex + hex; }).join('');
        }

        const r = parseInt(hexcolor.substring(0,2),16);
        const g = parseInt(hexcolor.substring(2,4),16);
        const b = parseInt(hexcolor.substring(4,6),16);

        // https://gomakethings.com/dynamically-changing-the-text-color-based-on-background-color-contrast-with-vanilla-js/
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;

        return (yiq >= 128) ? '#000000' : '#ffffff';
    }

}
