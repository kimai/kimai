/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import assert from 'node:assert/strict';
import { registerHooks } from 'node:module';
import { extname } from 'node:path';
import { test } from 'node:test';

// Resolve the extensionless imports used by the Webpack source files.
registerHooks({
    resolve(specifier, context, nextResolve) {
        if (specifier.startsWith('.') && extname(specifier) === '') {
            specifier += '.js';
        }
        return nextResolve(specifier, context);
    },
});

const { default: KimaiDateUtils } = await import('../../assets/js/plugins/KimaiDateUtils.js');
const dateUtils = new KimaiDateUtils();

for (const [input, expected] of [
    ['8.2', 29520],
    ['8,2', 29520],
    ['2.05', 7380],
    ['4.1', 14760],
    ['4.35', 15660],
    ['8.45', 30420],
    ['8.7', 31320],
    ['8.95', 32220],
    ['16.15', 58140],
    ['16.4', 59040],
    ['16.65', 59940],
    ['16.9', 60840],
    ['17.15', 61740],
    ['17.4', 62640],
    ['17.65', 63540],
    ['17.9', 64440],
    ['18.15', 65340],
    ['-8.2', -29520],
    ['0.5', 1800],
    ['8:12', 29520],
    ['8H12M', 29520],
]) {
    test(`duration ${input} keeps all ${expected} seconds`, () => {
        assert.equal(dateUtils.getSecondsFromDurationString(input), expected);
    });
}

test('integer-minutes mode still treats decimal inputs as hours', () => {
    assert.equal(dateUtils.getSecondsFromDurationString('8.2', 'integer_minutes'), 29520);
    assert.equal(dateUtils.getSecondsFromDurationString('12', 'integer_minutes'), 720);
});
