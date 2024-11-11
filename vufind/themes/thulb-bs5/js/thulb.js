function setupTruncations() {
    var truncatedParagraphs = $('p.truncate, td.truncate');
    var pTruncLength = 300;

    // apply truncations
    truncatedParagraphs.each(function() {
        var t = $(this).html();
        if(t.length < pTruncLength) {
            return;
        }

        $(this).html(
            t.slice(0,pTruncLength)+'<a href="#" class="more"> ' + VuFind.translate('truncate_more') + '</a>'+
            '<span style="display:none;">'+ t.slice(pTruncLength,t.length)+' <a href="#" class="less"> ' + VuFind.translate('truncate_less') + '</a></span>'
        );
    });

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

function setupFormOverlays() {
  $('#renewals, #cancelHold').on('submit', function renewalsOverlay() {
    let overlay = '<div class="form-loading-overlay">'
      + '<span class="form-loading-overlay-label">'
      + VuFind.loading()
      + "</span></div>";
    $(this).append(overlay);
  });
}

/**
 * Disply bootstrap for all links with the class hint and declared as tooltips
 */
function setupHintTooltips() {
    $('a.hint[data-toggle="tooltip"]').tooltip();
}

function setupPopovers() {
    $('[data-bs-toggle="popover').each(function (i) {
        $(this).on('click', function (e) {
            e.preventDefault();
        });
        new bootstrap.Popover(this, {
            html: true,
            delay: {
                show: 0,
                hide: 100
            },
            content: function (el) {
                return $(el).parent().find('.popover-link-list').html();
            }
        });
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

function styleHtmlTooltips() {
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

    jQuery('#' + message).effect('blind').dequeue().hide('fade').addClass('hidden');
}

function setupFormValidation() {
    // Doing error handling on form submit won't work here because the validation blocks the submit event from firing.

    // Get all the inputs...
    const inputs = document.querySelectorAll('input, select, textarea');

    // Loop through them...
    for(let input of inputs) {
        // Just before submit, the invalid event will fire, let's apply our class there.
        input.addEventListener('invalid', (event) => {
            input.classList.add('error');
        }, false);

        // Optional: Check validity onblur
        input.addEventListener('blur', (event) => {
            input.checkValidity();
        });
    }
}

$(document).ready(function thulbDocReady() {
    setupHintTooltips();
    setupTruncations();
    setupFormOverlays();
    setupFormValidation();
	  setAsyncResultNum();
    styleHtmlTooltips();

    checkVpnWarning(false);
    
    $('.checkbox-select-all').change(function unsetDisabledCheckboxes() {
        var $form = this.form ? $(this.form) : $(this).closest('form');
        $form.find('.checkbox-select-item:disabled').prop('checked', false);
    });
      
    // support other form input elements to auto submit
    $('input.jumpMenu').change(function jumpMenu(){ $(this).parent('form').submit(); });

    $("button[type='submit'][name='print']").on('click', function(event) {
        if ($('.checkbox-select-item:checked,checkbox-select-all:checked').length > 0) {
            $(this).closest('form').attr('target', '_blank');
        } else {
            $(this).closest('form').attr('target', '_self');
        }
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
