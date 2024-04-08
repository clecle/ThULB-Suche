/*global Hunt, VuFind */
VuFind.register('accessLookup', function accessLookup() {
    function displayAccessOptions(result, el) {

        let accessOptionsEl = $(el).find('.access-options');

        let loadingImg = $(accessOptionsEl).prev();
        if (loadingImg.is('img')) {
            loadingImg.hide();
        }

        if ("undefined" !== typeof result) {
            let actionRow = accessOptionsEl.parent().parent();
            let brokenLink = result.links.pop();
            if($(brokenLink).hasClass('broken-link')) {
                actionRow.append(result.links, actionRow.find('a'), brokenLink);
            }
            else {
                actionRow.append(result.links, brokenLink, actionRow.find('a'));
            }
        }
        else {
            accessOptionsEl.parent().hide();
        }
    }

    function accessLookupAjaxSuccess(items, response) {
        let idMap = {};

        // make map of ids to element arrays
        items.forEach(function mapItemId(item) {
            if (typeof idMap[item.id] === "undefined") {
                idMap[item.id] = [];
            }

            idMap[item.id].push(item.el);
        });

        // display data
        response.data.forEach(function displayAccessLookupResponse(accessLookup) {
            if (typeof idMap[accessLookup.id] === "undefined") {
                return;
            }

            idMap[accessLookup.id].forEach((el) => displayAccessOptions(accessLookup, el));
        });

        VuFind.lightbox.bind();

        VuFind.emit("access-lookup-done");
    }

    function accessLookupAjaxFailure(items, response, textStatus) {
        if (
            textStatus === "error" ||
            textStatus === "abort" ||
            typeof response.responseJSON === "undefined"
        ) {
            VuFind.emit("access-lookup-done");

            return;
        }

        // display the error message on each of the ajax status placeholder
        items.forEach(function displayAccessLookupFailure(item) {
            $(item.el)
                .find(".js-item-pending")
                .addClass("text-danger")
                .empty()
                .removeClass("hidden")
                .append(
                    typeof response.responseJSON.data === "string"
                        ? response.responseJSON.data
                        : VuFind.translate("error_occurred")
                );
        });

        VuFind.emit("access-lookup-done");
    }

    function makeAccessLookupQueue({
        url = "/AJAX/JSON?method=accessLookup",
        dataType = "json",
        method = "POST",
        delay = 200,
    } = {}) {
        return new AjaxRequestQueue({
            run: function runItemAjaxQueue(items) {
                return new Promise(function runItemAjaxPromise(done, error) {
                    const sid = VuFind.getCurrentSearchId();

                    $.ajax({
                        // todo: replace with fetch
                        url: VuFind.path + url,
                        data: { accessLookup: items.map((item) => item.id), sid },
                        dataType,
                        method,
                    })
                    .done(done)
                    .catch(error);
                });
            },
            success: accessLookupAjaxSuccess,
            failure: accessLookupAjaxFailure,
            delay,
        });
    }

    var accessLookupQueue = makeAccessLookupQueue();

    function checkAccessOptions(el) {
        var accessOptionsEl = el.querySelector(".access-options");

        if (
            accessOptionsEl === null ||
            el.classList.contains("js-item-pending") ||
            el.classList.contains("js-item-done")
        ) {
            return;
        }

        // update element to reflect lookup
        el.classList.add("js-item-pending");

        const loadingImg = el.querySelector("img.access-options-loading");
        if (loadingImg) {
            loadingImg.classList.remove("hidden");
        }

        // queue the element into the queue
        accessLookupQueue.add({ el, id: accessOptionsEl.dataset.accessLookup });
    }

    function checkAllAccessOptions(container = document) {
        container.querySelectorAll(".ajaxItem").forEach(checkAccessOptions);
    }

    function init($container = document) {
        const container = unwrapJQuery($container);

        if (VuFind.isPrinting()) {
            checkAccessOptions(container);
        } else {
            VuFind.observerManager.createIntersectionObserver(
                'accesslookup',
                checkAccessOptions,
                container.querySelectorAll('.ajax-access-lookup'),
                {}
            );
        }
    }

    return { init: init, check: checkAllAccessOptions, checkRecord: checkAccessOptions };
});
