/*global Hunt, VuFind */
VuFind.register('onlineContent', function onlineContent() {
    function displayOnlineContent(result, el) {

        var onlineContentEl = el.find('.online-content');

        var loadingImg = $(onlineContentEl).prev();
        if($(loadingImg).is('img')) {
            $(loadingImg).hide();
        }

        if ("undefined" !== typeof result) {
            $(onlineContentEl).empty();
            $(result).each(function (y, html) {
                $(onlineContentEl).append(html);
            });
        }
        else {
            $(onlineContentEl).parent().hide();
        }
    }
    var ItemStatusHandler = {
        name: "default",
        // Object that holds item IDs, states and elements
        items: {},
        url: '/AJAX/JSON?method=onlineContentLookup',
        itemStatusRunning: false,
        dataType: 'json',
        method: 'POST',
        itemStatusTimer: null,
        itemStatusDelay: 200,

        onlineContentLookupDone: function onlineContentLookupDone(response) {
            console.log(response.data);

            for(var i = 0; i < response.data.length; i++) {
                var id = response.data[i].id;
                this.items[id].result = response.data[i].result;
                this.items[id].state = 'done';
                for (var e = 0; e < this.items[id].elements.length; e++) {
                    displayOnlineContent(this.items[id].result, this.items[id].elements[e]);
                }
            }
        },
        onlineContentLookupFail: function onlineContentLookupFail(response, textStatus) {
            if (textStatus === 'error' || textStatus === 'abort' || typeof response.responseJSON === 'undefined') {
                return;
            }
            // display the error message on each of the ajax status place holder
            $('.js-item-pending').addClass('text-danger').empty().removeClass('hidden')
                .append(typeof response.responseJSON.data === 'string' ? response.responseJSON.data : VuFind.translate('error_occurred'));
        },
        itemQueueAjax: function itemQueueAjax(id, el) {
            el.addClass('js-item-pending').removeClass('hidden');
            // If this id has already been queued, just add it to the elements or display a
            // cached result.
            if (typeof this.items[id] !== 'undefined') {
                if ('done' === this.items[id].state) {
                    displayOnlineContent(this.items[id].result, el);
                } else {
                    this.items[id].elements.push(el);
                }
                return;
            }
            clearTimeout(this.itemStatusTimer);
            this.items[id] = {
                id: id,
                state: 'queued',
                elements: [el]
            };
            this.itemStatusTimer = setTimeout(this.runItemAjaxForQueue.bind(this), this.itemStatusDelay);
        },

        runItemAjaxForQueue: function runItemAjaxForQueue() {
            if (this.itemStatusRunning) {
                this.itemStatusTimer = setTimeout(this.runItemAjaxForQueue.bind(this), this.itemStatusDelay);
                return;
            }
            var ids = [];
            var self = this;
            $.each(this.items, function selectQueued() {
                if ('queued' === this.state) {
                    self.items[this.id].state = 'running';
                    ids.push(this.id);
                }
            });
            $.ajax({
                dataType: this.dataType,
                method: this.method,
                url: VuFind.path + this.url,
                context: this,
                data: { 'onlineContent': ids }
            })
            .done(this.onlineContentLookupDone)
            .fail(this.onlineContentLookupFail)
            .always(function queueAjaxAlways() {
                this.itemStatusRunning = false;
            });
        }//end runItemAjaxForQueue
    };

    function checkOnlineContent(el) {
        var onlineContentEl = $(el).find('.online-content');

        $(onlineContentEl).each(function (index, element) {
            var currentOnlineContent = $(element).data('onlineContent');

            var loadingImg = $(onlineContentEl).prev();
            if($(loadingImg).is('img')) {
                $(loadingImg).show();
            }

            ItemStatusHandler.itemQueueAjax(currentOnlineContent, $(el));
        });
    }

    // Assign actions to the OpenURL links. This can be called with a container e.g. when
    // combined results fetched with AJAX are loaded.
    function init(_container) {
        var container = _container || $('body');
        // assign action to the openUrlWindow link class
        if (typeof Hunt === 'undefined') {
            checkOnlineContent(container);
        } else {
            new Hunt(
                container.find('.ajaxItem').toArray(),
                { enter: checkOnlineContent }
            );
        }
    }


    return {
        init: init,
        embedOnlineContent: checkOnlineContent
    };
});
