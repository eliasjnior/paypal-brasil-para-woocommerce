import Vue from 'vue'
import Component from "vue-class-component";

// This is the WordPress localized settings.
declare const paypal_payments_admin_options_plus: {
    template: string,
    title: string,
    mode: string,
    live_client_id: string,
    live_secret: string,
    sandbox_client_id: string,
    sandbox_secret: string,
    debug: string,
};

@Component({
    template: paypal_payments_admin_options_plus.template,
})
export default class AdminOptionsPlus extends Vue {

    enabled: string;
    title: string;
    mode: string;

    constructor() {
        super();

        this.enabled = paypal_payments_admin_options_plus.title
        this.title = paypal_payments_admin_options_plus.title;
        this.mode = paypal_payments_admin_options_plus.mode || 'live';
    }

    isLive() {
        return this.mode === 'live';
    }

    isEnabled() {
        return this.enabled === '1';
    }

}

new Vue({
    el: '#admin-options-plus',
    render: h => h(AdminOptionsPlus)
});