/*global Hunt, VuFind */
VuFind.register('onlineContent', function onlineContent() {
    function displayOnlineContent(result, el) {

        let onlineContentEl = $(el).find('.online-content');

        let loadingImg = $(onlineContentEl).prev();
        if(loadingImg.is('img')) {
            loadingImg.hide();
        }

        if ("undefined" !== typeof result) {
            let actionRow = onlineContentEl.parent().parent();
            let brokenLink = result.links.pop();
            if($(brokenLink).hasClass('broken-link')) {
                actionRow.append(result.links, actionRow.find('a'), brokenLink);
            }
            else {
                actionRow.append(result.links, brokenLink, actionRow.find('a'));
            }
        }
        else {
            onlineContentEl.parent().hide();
        }
    }

    function onlineContentAjaxSuccess(items, response) {
        let idMap = {};

        // make map of ids to element arrays
        items.forEach(function mapItemId(item) {
            if (typeof idMap[item.id] === "undefined") {
                idMap[item.id] = [];
            }

            idMap[item.id].push(item.el);
        });

        // display data
        response.data.forEach(function displayOnlineContentResponse(onlineContent) {
            if (typeof idMap[onlineContent.id] === "undefined") {
                return;
            }

            idMap[onlineContent.id].forEach((el) => displayOnlineContent(onlineContent, el));
        });

        VuFind.emit("online-content-done");
    }

    function onlineContentAjaxFailure(items, response, textStatus) {
        if (
            textStatus === "error" ||
            textStatus === "abort" ||
            typeof response.responseJSON === "undefined"
        ) {
            VuFind.emit("online-content-done");

            return;
        }

        // display the error message on each of the ajax status placeholder
        items.forEach(function displayOnlineContentFailure(item) {
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

        VuFind.emit("online-content-done");
    }

    function makeOnlineContentQueue({
        url = "/AJAX/JSON?method=onlineContentLookup",
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
                        data: { onlineContent: items.map((item) => item.id), sid },
                        dataType,
                        method,
                    })
                    .done(done)
                    .catch(error);
                });
            },
            success: onlineContentAjaxSuccess,
            failure: onlineContentAjaxFailure,
            delay,
        });
    }

    var onlineContentQueue = makeOnlineContentQueue();

    function checkOnlineContent(el) {
        var onlineContentEl = el.querySelector(".online-content");

        if (
            onlineContentEl === null ||
            el.classList.contains("js-item-pending") ||
            el.classList.contains("js-item-done")
        ) {
            return;
        }

        // update element to reflect lookup
        el.classList.add("js-item-pending");

        const loadingImg = el.querySelector("img.online-content-loading");
        if (loadingImg) {
            loadingImg.classList.remove("hidden");
        }

        // queue the element into the queue
        onlineContentQueue.add({ el, id: onlineContentEl.dataset.onlineContent });
    }

    function checkAllOnlineContents(container = document) {
        container.querySelectorAll(".ajaxItem").forEach(checkOnlineContent);
    }

    function init($container = document) {
        const container = unwrapJQuery($container);

        if (VuFind.isPrinting()) {
            checkOnlineContent(container);
        } else {
            VuFind.observerManager.createIntersectionObserver(
                'onlineContents',
                checkOnlineContent,
                container.querySelectorAll('.ajax-online-content')
            );
        }
    }

    return { init: init, check: checkAllOnlineContents, checkRecord: checkOnlineContent };
});
