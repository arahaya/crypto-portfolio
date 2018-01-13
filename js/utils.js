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

// modified https://stackoverflow.com/a/34817120/108156
function number_format(number, decimals, options) {
    number   = Number(number);
    decimals = decimals || 0;
    options  = options  || {};
    var thousands_separator = options.thousands_separator || ',';
    var decimal_point       = options.decimal_point       || '.';
    var with_jp_prefix      = options.with_jp_prefix      || false;
    var significant_figures = options.significant_figures || null;

    var PREFIX_CRITERIA = 3;

    var round = function (num, dec) {
      num = num * Math.pow(10, dec);
      num = Math.round(num);
      return num / Math.pow(10, dec);
    }

    var prefix_offset = 0;
    if (with_jp_prefix) {
        if (number >= Math.pow(10, 4 + PREFIX_CRITERIA)) {
            prefix_offset = 4;
        }
        number = number / Math.pow(10, prefix_offset);
    }

    if (significant_figures && number < Math.pow(10, significant_figures - decimals)) {
        decimals = significant_figures - 1 - Math.floor(Math.log(number) / Math.log(10));
    }

    number = round(number, decimals);

    var strs = number.toFixed(decimals).toString().split('.');

    var parts = [];
    for (var i = strs[0].length; i > 0; i -= 3) {
        parts.unshift(strs[0].substring(Math.max(0, i - 3), i));
    }
    strs[0] = parts.join(thousands_separator);

    var number_str = strs.join(decimal_point);

    if (prefix_offset === 4) {
        number_str = number_str + '万';
    }

    return number_str
}

function startsWith(haystack, needle){
    return haystack.substr(0, needle.length) === needle;
}
