jQuery(function ($) {
    $(".dropdown-button").dropdown();
    $(".button-collapse").sideNav();
    $('select').material_select();
});

function confirmUrl(url, message) {
    if (undefined === message) {
        message = 'Are you sure?';
    }
    if (confirm(message)) {
        document.location = url;
    }
}
