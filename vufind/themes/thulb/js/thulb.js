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
    
    truncatedLinks.each(function() {
        var t = $(this).text();
        var visibleWidth = $(this).parent().innerWidth();
        if(t.length > (aTruncRatio * visibleWidth)) {
            var h = $(this).html();
            $(this).tooltip({title: h, delay: {show: 500, hide: 100}, html: true, placement: 'auto', container: 'body'});
        }
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

/**
 * Setup facets
 */
function setupThulbFacets() {
  $('ul[class=pagination] li,select[name=sort] option,.authorLink,.langOption,.facetAND,.facetOR,.facetTAB,.facetRANGE,.checkbox-filter').click(function resultlistOverlay() {
    $("#searchcontent").css('pointer-events', 'none');
    $("#searchcontent").css('opacity', '0.5');

    $("#img-load").css({
      top   : '50%',
      left  : '50%'
    });
    
    $("#overlay").fadeIn();
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
  $('.facet.list-group .collapse').on('shown.bs.collapse', facetSessionStorage);
  $('.facet.list-group .collapse').on('hidden.bs.collapse', facetSessionStorage);
}

/**
 * Setup search box and icon to remove the content
 */
function setupRemoveSearchText() {
    var search = $('#searchForm_lookfor');
    var icon = $('#search-delete-icon');

    if (search.val() !== '') {
        icon.show();
    }
    icon.click(function () {
        search.val('').focus();
        icon.hide();
    });
    search.on('input', function () {
        search.val() === '' ? icon.hide() : icon.show();
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
            $('span.resultNum' + index).text(response.data['count']);
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

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires + ";path=/";
}

function hideMessage(message) {
    console.log(message);

    $.ajax({
        dataType: 'json',
        method: 'POST',
        url: VuFind.path + '/AJAX/JSON?method=hideMessage',
        data: {'message': message}
    });

    jQuery('#' + message).effect('blind').dequeue().hide('fade');
}

$(document).ready(function thulbDocReady() {
    setupTruncations();
    setupThulbFacets();
    setupRemoveSearchText();
	setAsyncResultNum();
    styleHtmlTooltips();
    
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
});

document.addEventListener('VuFind.lightbox.rendered', function(event) {
    $("button[type='submit'][name='print']").on('click', function(event) {
        $(this).closest('form').attr('target', '_blank');
    });
}, false);