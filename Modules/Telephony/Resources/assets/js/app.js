Vue.component('telephony', require('./Telephony'));
import VueInternationalization from 'vue-i18n';
Vue.component('machine-search', require('./machine-search'));

const lang = document.documentElement.lang.substr(0, 2);
const __messages = Object.assign(window.lang_messages)
const i18n = new VueInternationalization({
    locale: lang,
    fallbackLocale: 'ru',
    messages: __messages
});
window.showBootstrapMessageErrors = function (data) {
    let er, msg = '';
    for (er in data) {
        msg += data[er] + '<br>';
    }

    swal.fire('Внимание!', msg, 'error');
}
window.clearBootstrapErrors = function () {
    $('.form-control-feedback').remove()
    $('.is-invalid').each(function () {
        $(this).removeClass('is-invalid');
    })
}
window.showBootstrapErrors = function (response) {
    if ($("div.g-recaptcha").length > 0) {
        grecaptcha.reset();
    }
    clearBootstrapErrors()
    var d = response

    if ('modals' in d) {
        showBootstrapMessageErrors(d.modals)
    }
    let key, k;
    for (key in d) {
        var u = d[key]
        var ms = '';
        var input = $('body').find('[name="' + key + '"]');
        var par = input.closest('.form-control');

        par.addClass('is-invalid')

        for (k in u) {
            ms += u[k] + ' ';
        }
        console.log(ms);
        if (input.attr('type') !== 'checkbox' && input.attr('type') !== 'radio') {
            $(' <div class="form-control-feedback invalid-feedback">' + ms + '</div>').insertAfter(input);
        }
    }
};

const app = new Vue({
    el: '#app',
    i18n,
});