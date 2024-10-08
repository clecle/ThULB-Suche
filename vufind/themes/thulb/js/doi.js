/*global Hunt, VuFind */
VuFind.register('doi', function Doi() {
  function embedDoiLinks(el) {
    var element = $(el);
    var doi = [];
    var elements = element.hasClass('doiLink') ? element : element.find('.doiLink');
    elements.each(function extractDoiData(i, doiLinkEl) {
      var currentDoi = $(doiLinkEl).data('doi');
      if (doi.indexOf(currentDoi) === -1) {
        doi[doi.length] = currentDoi;
      }
    });
    if (doi.length === 0) {
      return;
    }
    var url = VuFind.path + '/AJAX/JSON?' + $.param({
      method: 'doiLookup',
      doi: doi,
    });
    $.ajax({
      dataType: 'json',
      url: url
    })
      .done(function embedDoiLinksDone(response) {
        elements.each(function populateDoiLinks(x, doiEl) {
          var currentDoi = $(doiEl).data('doi');

          var loadingImg = $(doiEl).prev();
          if($(loadingImg).is('img')) {
            $(loadingImg).hide();
          }

          if ("undefined" !== typeof response.data[currentDoi]) {
            $(doiEl).empty();
            var unpaywallIcon = $('<div />');
            unpaywallIcon.html('<i class="fa fa-unlock-alt"></i>');
            for (var i = 0; i < response.data[currentDoi].length; i++) {
              var newLink = $('<a />');
              var source = response.data[currentDoi][i].source;
              newLink.attr('href', response.data[currentDoi][i].link);
              newLink.attr('target', '_blank');
              newLink.attr('class', 'btn btn-primary btn-xs');
              $('<span/>')
                .addClass('icon-link__label')
                .text(response.data[currentDoi][i].label)
                .appendTo(newLink);
              if (response.data[currentDoi][i].newWindow) {
                newLink.attr('target', '_blank');
              }
              if (typeof source !== 'undefined') {
                newLink.addClass(source);
                if (source === 'unpaywall') {
                  newLink.prepend(unpaywallIcon);
                }
              }
              newLink.attr('rel', 'noreferrer');
              if (typeof response.data[currentDoi][i].icon !== 'undefined') {
                var icon = $('<img />');
                icon.attr('src', response.data[currentDoi][i].icon);
                icon.addClass('doi-icon icon-link__icon');
                newLink.prepend(icon);
              }
              $(doiEl).append(newLink);
              if(!$(doiEl).next().is('a')) {
                $(doiEl).append("<br />");
              }
            }
          }
          else {
            $(doiEl).parent().hide();
          }
        });
      });
  }

  function updateContainer(params) {
    embedDoiLinks(params.container);
  }

  // Assign actions to the OpenURL links. This can be called with a container e.g. when
  // combined results fetched with AJAX are loaded.
  function init(_container) {
    var container = unwrapJQuery(_container || document.body);
    // assign action to the openUrlWindow link class
    if (VuFind.isPrinting()) {
      embedDoiLinks(container);
    } else {
      VuFind.observerManager.createIntersectionObserver(
        'doiLinks',
        embedDoiLinks,
        Array.from(container.querySelectorAll('.doiLink'))
      );
    }
    VuFind.listen('results-init', updateContainer);
  }
  return {
    init: init,
    embedDoiLinks: embedDoiLinks
  };
});
