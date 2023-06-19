function calculatePrice() {
    $('#chargeCost').html(
        new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(
            $('#chargeQuantity').val() * creditPrice
    ));
}

function increaseCredits() {
    let el = $('#chargeQuantity');
    if(el.val() < el.prop('max')) {
        el.val(Number(el.val()) + 1);
        calculatePrice();
    }
}

function decreaseCredits() {
    let el = $('#chargeQuantity');
    if(el.val() > el.prop('min')) {
        el.val(Number(el.val()) - 1);
        calculatePrice();
    }
}

function toggleWorkRelated() {
    $('#department, #facility').each(function () {
        $(this).prop('required', !$(this).prop('required')).toggle();
    });
}