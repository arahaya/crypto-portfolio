// https://tc39.github.io/ecma262/#sec-array.prototype.find
if (!Array.prototype.find) {
    Object.defineProperty(Array.prototype, 'find', {
        value: function (predicate) {
            // 1. Let O be ? ToObject(this value).
            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }

            var o = Object(this);

            // 2. Let len be ? ToLength(? Get(O, "length")).
            var len = o.length >>> 0;

            // 3. If IsCallable(predicate) is false, throw a TypeError exception.
            if (typeof predicate !== 'function') {
                throw new TypeError('predicate must be a function');
            }

            // 4. If thisArg was supplied, let T be thisArg; else let T be undefined.
            var thisArg = arguments[1];

            // 5. Let k be 0.
            var k = 0;

            // 6. Repeat, while k < len
            while (k < len) {
                // a. Let Pk be ! ToString(k).
                // b. Let kValue be ? Get(O, Pk).
                // c. Let testResult be ToBoolean(? Call(predicate, T, « kValue, k, O »)).
                // d. If testResult is true, return kValue.
                var kValue = o[k];
                if (predicate.call(thisArg, kValue, k, o)) {
                    return kValue;
                }
                // e. Increase k by 1.
                k++;
            }

            // 7. Return undefined.
            return undefined;
        }
    });
}

if (!window.localStorage) {
    (function () {
        var data = {};

        window.localStorage = {
            setItem: function (key, value) {
                data[key] = value;
                return data[key];
            },
            getItem: function (key) {
                return (key in data) ? data[key] : undefined;
            },
            removeItem: function (key) {
                delete data[key];
                return undefined;
            },
            clear: function () {
                data = {};
                return data;
            }
        };
    });
}

// https://stackoverflow.com/a/34817120/108156
function number_format(number,decimals,dec_point,thousands_sep) {
    // makes sure `number` is numeric value
    number = number * 1;
    // multiply by decimals
    number = number * Math.pow(10, decimals ? decimals : 0);
    // round
    number = Math.round(number);
    // devide by decimals
    number = number / Math.pow(10, decimals ? decimals : 0);

    var str = number.toFixed(decimals ? decimals : 0).toString().split('.');
    var parts = [];

    for (var i = str[0].length; i > 0; i -= 3) {
        parts.unshift(str[0].substring(Math.max(0, i - 3), i));
    }

    str[0] = parts.join(thousands_sep ? thousands_sep : ',');
    return str.join(dec_point ? dec_point : '.');
}

function startsWith(haystack, needle){
    return haystack.substr(0, needle.length) === needle;
}