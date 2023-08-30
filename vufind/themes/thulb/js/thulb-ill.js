function calculatePrice() {
    let el = $('#chargeQuantity');
    let val = Number(el.val());
    let min = Number(el.prop('min'));
    let max = Number(el.prop('max'));

    if(val < min) {
        el.val(min);
    }
    if(val > max) {
        el.val(max);
    }

    $('#chargeCost').html(
        new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(
            $('#chargeQuantity').val() * creditPrice
    ));
}

function increaseCredits() {
    let el = $('#chargeQuantity');
    let val = Number(el.val());
    let max = Number(el.prop('max'));
    if(val < max) {
        el.val(++val);
        calculatePrice();
    }
}

function decreaseCredits() {
    let el = $('#chargeQuantity');
    let val = Number(el.val());
    let min = Number(el.prop('min'));
    if(val > min) {
        el.val(--val);
        calculatePrice();
    }
}

function toggleWorkRelated() {
    $('#department, #facility').each(function () {
        $(this).prop('required', !$(this).prop('required')).toggleClass('hidden');
    });

    $('#ill-cost').toggle();
}