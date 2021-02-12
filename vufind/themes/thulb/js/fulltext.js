/*global Hunt, VuFind */
VuFind.register('fulltext', function Fulltext() {
    function embedFulltextLinks(el) {
        var element = $(el);
        var fulltext = [];
        var elements = element.hasClass('fulltextLink') ? element : element.find('.fulltextLink');
        elements.each(function extractFulltextData(i, fulltextLinkEl) {
            var currentFulltext = $(fulltextLinkEl).data('fulltext');
            if (fulltext.indexOf(currentFulltext) === -1) {
                fulltext[fulltext.length] = currentFulltext;
            }
        });
        if (fulltext.length === 0) {
            return;
        }
        var url = VuFind.path + '/AJAX/JSON?' + $.param({
            method: 'fulltextLookup',
            fulltext: fulltext,
        });
        $.ajax({
            dataType: 'json',
            url: url
        })
        .done(function embedFulltextLinksDone(response) {
            elements.each(function populateFulltextLinks(x, fulltextEl) {
                var currentFulltext = $(fulltextEl).data('fulltext');
                console.log(response.data[currentFulltext]);

                var loadingImg = $(fulltextEl).prev();
                if($(loadingImg).is('img')) {
                    $(loadingImg).hide();
                }

                if ("undefined" !== typeof response.data[currentFulltext]) {
                    $(fulltextEl).empty();
                    var newLink = $('<a />');
                    newLink.attr('href', response.data[currentFulltext].link);
                    newLink.attr('target', '_blank');
                    newLink.attr('class', 'btn btn-primary btn-xs external-link');
                    newLink.text(' ' + response.data[currentFulltext].desc);
                    $(fulltextEl).append(newLink);
                    if(!$(fulltextEl).next().is('a')) {
                        $(fulltextEl).append("<br />");
                    }
                }
                else {
                    $(fulltextEl).parent().hide();
                }
            });
        });
    }

    // Assign actions to the OpenURL links. This can be called with a container e.g. when
    // combined results fetched with AJAX are loaded.
    function init(_container) {
        var container = _container || $('body');
        // assign action to the openUrlWindow link class
        if (typeof Hunt === 'undefined') {
            embedFulltextLinks(container);
        } else {
            new Hunt(
                container.find('.fulltextLink').toArray(),
                { enter: embedFulltextLinks }
            );
        }
    }
    return {
        init: init,
        embedFulltextLinks: embedFulltextLinks
    };
});
