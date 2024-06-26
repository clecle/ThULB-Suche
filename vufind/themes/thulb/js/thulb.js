function setupTruncations() {
    var truncatedParagraphs = $('p.truncate');
    var truncatedLinks = $('a.truncate');
    var pTruncLength = 300;
    var aTruncRatio = 0.25;
    
    // apply truncations
    truncatedParagraphs.each(function() { 
        var t = $(this).text();
        if(t.length < pTruncLength) {
            return;
        }
        
        $(this).html(
            t.slice(0,pTruncLength)+'<a href="#" class="more"> ' + VuFind.translate('truncate_more') + '</a>'+
            '<span style="display:none;">'+ t.slice(pTruncLength,t.length)+' <a href="#" class="less"> ' + VuFind.translate('truncate_less') + '</a></span>'
        );
    });
    
//    truncatedLinks.each(function() {
//        var t = $(this).text();
//        var visibleWidth = $(this).parent().innerWidth();
//        if(t.length > (aTruncRatio * visibleWidth)) {
//            var h = $(this).html();
//            $(this).tooltip({title: h, delay: {show: 500, hide: 100}, html: true, placement: 'auto', container: 'body'});
//        }
//    });
    
    // setup additional behaviour to show full content
    $('a.more', truncatedParagraphs).click(function(event){
        event.preventDefault();
        $(this).hide().prev().hide();
        $(this).next().show();        
    });
    
    $('a.less', truncatedParagraphs).click(function(event){
        event.preventDefault();
        $(this).parent().hide().prev().show().prev().show();    
    });
}

/**
 * Setup facets
 */
function setupThulbFacets() {
  $('ul[class=pagination] li,select[name=sort] option,.authorLink,.langOption,.facetAND,.facetOR,.facetTAB,.facet-range-form input[type=submit],.checkbox-filter, #renewSelected, #renewAll').click(function resultlistOverlay() {
    $("#content").css('pointer-events', 'none');
    $("#content").css('opacity', '0.5');

    $("#img-load").css({
      top   : '50%',
      left  : '50%'
    });

    $("#img-load").fadeIn();
  });

  // Side facet status saving
  $('.facet.list-group .collapse').each(function openStoredFacets(index, item) {
    var source = $('#result0 .hiddenSource').val();
    var storedItem = sessionStorage.getItem('sidefacet-' + source + item.id);
    if (storedItem) {
      var saveTransition = $.support.transition;
      try {
        $.support.transition = false;
        if ((' ' + storedItem + ' ').indexOf(' in ') > -1) {
          $(item).collapse('show');
        } else {
          $(item).collapse('hide');
        }
      } finally {
        $.support.transition = saveTransition;
      }
    }
  });
  // $('.facet.list-group .collapse').on('shown.bs.collapse', facetSessionStorage);
  // $('.facet.list-group .collapse').on('hidden.bs.collapse', facetSessionStorage);
}

/**
 * Show or hide the delete search icon and add 'with-delete-icon'-class to search field.
 * @param show Boolean to show or hide the icon.
 */
function toggleDeleteSearchIcon(show) {
    $('#searchForm_lookfor').toggleClass('with-delete-icon', show);
    $('#search-delete-icon').toggle(show);
}

/**
 * Setup search box and icon to remove the content
 */
function setupRemoveSearchText() {
    var search = $('#searchForm_lookfor');
    toggleDeleteSearchIcon(search.val() !== '');
    search.on('input', function () {
        toggleDeleteSearchIcon(search.val() !== '');
    });

    $('#search-delete-icon').click(function () {
        search.val('').focus();
        toggleDeleteSearchIcon(false);
    });
}

/**
 * Disply bootstrap for all links with the class hint and declared as tooltips
 */
function setupHintTooltips() {
    $('a.hint[data-toggle="tooltip"]').tooltip();
}

function setupPopovers() {
    $('[data-toggle="popover')
        .on('click',function(e){
            e.preventDefault();
        })
        .popover({
        html : true,
        delay: {
            show: 0,
            hide: 100
        },
        content: function() {
            return $(this).parent().find('.popover-link-list').html();
        }
    });
}


function setAsyncResultNum() {
    var lookfor = $('#searchForm_lookfor').val();
    var type = $('#searchForm_type option:checked').val();
    var index = '';

    if (['AllFields', 'Title', 'Author', 'Subject', 'SubjectTerms'].indexOf(type) < 0) {
        type = 'AllFields';
    } else if (type === 'Subject') {
        type = 'SubjectTerms';
    } else if (type === 'SubjectTerms') {
        type = 'Subject';
    }
    
    if ($('span.resultNumSummon').length) {
        index = 'Summon';
    } else if ($('span.resultNumSolr').length) {
        index = 'Solr';
    }
    
    if (index.length > 0) {
        $.ajax({
            dataType: 'json',
            method: 'POST',
            url: VuFind.path + '/AJAX/JSON?method=getResultCount',
            data: {'lookfor': lookfor, 'index': index, 'type': type}
        }).done(function writeCount (response) {
            $('span.resultNum' + index).text(response.data.count);
        }).fail(function() {
            $('span.resultNum' + index).addClass('hidden');
        });
    }
}

function styleHtmlTooltips()
{
    $('.html-tooltip').each(function() {
        $(this).tooltip({delay: {show: 500, hide: 100}, html: true, placement: 'auto', container: 'body'});
    });
}

function toggleVpnWarning(show, html) {
    var ajaxMessage = $('.vpn-warning-wrapper');
    if(typeof html !== 'undefined' && html !== '') {
        ajaxMessage.html(html);
    }

    ajaxMessage.animate({
        'opacity': show,      // animate slideUp
        'margin-top':  show,
        'margin-bottom':  show,
        'padding-top': show,
        'padding-bottom': show,
        'height':  show
    }, 700);
}

function checkVpnWarning(accepted) {
    if(accepted) {
        toggleVpnWarning('hide');
    }

    $.ajax({
        dataType: 'json',
        method: 'POST',
        url: VuFind.path + '/AJAX/JSON?method=vpnWarning' + (accepted ? '&vpn-accepted=ja' : '')
    }).done(function showVpnWarning (response) {
        if(response.data.html) {
            toggleVpnWarning('show', response.data.html);
        }
    });
}

function hideMessage(message) {
    $.ajax({
        dataType: 'json',
        method: 'POST',
        url: VuFind.path + '/AJAX/JSON?method=hideMessage',
        data: {'message': message}
    });

    jQuery('#' + message).effect('blind').dequeue().hide('fade');
}

$(document).ready(function thulbDocReady() {
    setupHintTooltips();
    setupTruncations();
    setupThulbFacets();
    setupRemoveSearchText();
	setAsyncResultNum();
    styleHtmlTooltips();

    checkVpnWarning(false);
    
    $('.checkbox-select-all').change(function unsetDisabledCheckboxes() {
        var $form = this.form ? $(this.form) : $(this).closest('form');
        $form.find('.checkbox-select-item:disabled').prop('checked', false);
    });
      
    // support other form input elements to auto submit
    $('input.jumpMenu').change(function jumpMenu(){ $(this).parent('form').submit(); });

    /*
     * smooth vertical scroll through page
     * mainly used for filter-button in mobile-view
     */
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if( target.length ) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top
            }, 1000);
        }
    });
    
    $("button[type='submit'][name='print']").on('click', function(event) {
        if ($('.checkbox-select-item:checked,checkbox-select-all:checked').length > 0) {
            $(this).closest('form').attr('target', '_blank');
        } else {
            $(this).closest('form').attr('target', '_self');
        }
    });

    // retain filter sessionStorage
    $('.searchFormKeepFilters').click(function retainFiltersInSessionStorage() {
        $('.applied-filter').prop('checked', this.checked);
    });

    // open cart when full
    $('.cart-add').on('click', function (event){
        if(VuFind.cart.getFullItems().length >= parseInt(VuFind.translate('bookbagMax'), 10)) {
            $('#cartItems').click();
        }
    });

    // enable submenus
    $('ul.dropdown-menu [data-toggle=dropdown]').on('click', function(event) {
        // Avoid following the href location when clicking
        event.preventDefault();
        // Avoid having the menu to close when clicking
        event.stopPropagation();

        let isOpen = $(this).parent().hasClass('open');

        // If a menu is already open we close it
        $('ul.dropdown-menu [data-toggle=dropdown]').parent().removeClass('open');
        // opening the one you clicked on
        $(this).parent().toggleClass('open', !isOpen);

        let menu = $(this).parent().find("ul");
        let menuPos = menu.offset();

        let newPos;
        if ((menuPos.left + menu.width()) + 30 > $(window).width()) {
            newPos = - menu.width();
        }
        else {
            newPos = $(this).parent().width();
        }
        menu.css({ left:newPos });
    });
});

document.addEventListener('VuFind.lightbox.rendered', function(event) {
    $("button[type='submit'][name='print']").on('click', function(event) {
        $(this).closest('form').attr('target', '_blank');
    });
}, false);