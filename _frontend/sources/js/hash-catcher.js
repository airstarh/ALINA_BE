$(document).ready(function () {

    ALINA.hash = ALINA.hash || {
        clean: function(event)
        {
            // for older browsers, leaves a # behind
            // hash no_where - to prevent page jump to top (!important)
            window.location.hash = 'no_where';
            if (history.pushState) {
                history.pushState('', document.title, window.location.pathname); // nice and clean
            }
            if (event) {
                event.preventDefault(); // no page reload
            }
        },

        analyze: function () {
            var hash = window.location.hash.substring(1);

            if (!hash || hash.length == 0) return false;

            var hashArray = hash.split('&');
            var hashQueryObj = {};
            for (pair in hashArray) {
                var eachHashPair = hashArray[pair].split('=');
                if (eachHashPair.length === 2) {
                    hashQueryObj[eachHashPair[0]] = eachHashPair[1];
                } else {
                    if (eachHashPair[0] && eachHashPair[0] != " ") {
                        hashQueryObj[eachHashPair[0]] = eachHashPair[0];
                    }
                }
            }
            ALINA.hash.obj = hashQueryObj;
            return hashQueryObj;
        },

        handle: function (event) {
            hashQueryObj = ALINA.hash.analyze();
            // prevent page jumping if hash was cleaned
            if (hashQueryObj == false) {
                if (event) {event.preventDefault();}
                return false;
            }

            // ALINA popup
            if (hashQueryObj['popup']) {
                ALINA.popup.open(hashQueryObj['popup']);
            }
            return true;
        }
    };

    // After all DOM handlers only!
    // Read the hash, bind Hash Change Events
    $(window).on('hashchange', function (event) {
        ALINA.hash.handle(event);
    });
    ALINA.hash.handle();
});
