(function (undefined) {
    'use strict';

    window.Storage = function () {
    }
})(void 0);

(function (undefined) {
    'use strict';

    window.LocalStorage = function () {
        Storage.call(this);
    }

    LocalStorage.prototype = Object.create(Storage.prototype);
    LocalStorage.prototype.constructor = LocalStorage;

    LocalStorage.prototype.getItem = function (key) {
        var item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    }

    LocalStorage.prototype.setItem = function (key, item) {
        localStorage.setItem(key, JSON.stringify(item));
    }
})(void 0);

(function (undefined) {
    'use strict';

    window.SessionManager = function (storage) {
        this.storage = storage;
    }

    SessionManager.prototype.getSession = function () {
        var session_v2 = this.storage.getItem('store_v2');

        if (!session_v2) {
            session_v2 = this.storage.getSessionV1AsV2();
        }

        return session_v2 ? session_v2 : {portfolio: []};
    };

    SessionManager.prototype.getSessionV1AsV2 = function () {
        var session_v1 = this.storage.getItem('store');
        var session_v2;

        if (session_v1) {
            session_v2 = {portfolio: []};

            for (var exchange in session_v1) {
                if (session_v1.hasOwnProperty(exchange)) {
                    for (var currency in session_v1[exchange]) {
                        if (session_v1[exchange].hasOwnProperty(currency)) {
                            var balance = session_v1[exchange][currency];
                            var currency_id;

                            // Polo Stellar fix
                            if (currency == 'STR') {
                                currency = 'XLM';
                            }

                            for (var i = 0, l = this.currency_data.length; i < l; i++) {
                                var d = this.currency_data[i];

                                if (d.symbol == currency) {
                                    currency_id = d.id;
                                    break;
                                }
                            }

                            if (!currency_id) {
                                continue;
                            }

                            session_v2.portfolio.push({
                                currency: currency_id,
                                balance: balance,
                                memo: ''
                            });
                        }
                    }
                }
            }
        }

        return session_v2;
    };

    SessionManager.prototype.saveSession = function (portfolio) {
        var session = {
            portfolio: []
        };

        portfolio.forEach(function (item) {
            session.portfolio.push({
                currency: item.currency,
                balance: item.balance,
                memo: item.memo
            });
        });

        this.storage.setItem('store_v2', session);
    };
})(void 0);
