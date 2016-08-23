jQuery(function ($) {
    $(".dropdown-button").dropdown();
    $(".button-collapse").sideNav();
    // the "href" attribute of .modal-trigger must specify the modal ID that wants to be triggered
    $('.modal-trigger').leanModal();
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
