(function (undefined) {
    'use strict';

    window.Storage = function () {
    }

    window.LocalStorage = function () {
        Storage.call(this);
    }

    LocalStorage.prototype = Object.create(Storage.prototype);
    LocalStorage.prototype.constructor = LocalStorage;

    LocalStorage.prototype.getItem = function (key, callback) {
        var item = localStorage.getItem(key);
        callback(item ? JSON.parse(item) : null);
    }

    LocalStorage.prototype.setItem = function (key, item, callback) {
        localStorage.setItem(key, JSON.stringify(item));
        callback && callback();
    }
})(void 0);

(function (undefined) {
    'use strict';

    window.SessionManager = function (storage) {
        this.storage = storage;
    }

    SessionManager.prototype.getSession = function (callback) {
        this.storage.getItem('store_v2', function (session_v2) {
            if (!session_v2) {
                this.getSessionV1AsV2(function (session_v1_as_v2) {
                    callback(session_v1_as_v2 || {portfolio: []});
                });
            }
            callback(session_v2 || {portfolio: []});
        }.bind(this));
    };

    SessionManager.prototype.getSessionV1AsV2 = function (callback) {
        this.storage.getItem('store', function (session_v1) {
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

            callback(session_v2);
        }.bind(this));
    };

    SessionManager.prototype.saveSession = function (portfolio, callback) {
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

        this.storage.setItem('store_v2', session, callback);
    };
})(void 0);
