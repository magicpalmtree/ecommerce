import Area from "../../../../../../../../js/production/area.js";

function Title() {
    return React.createElement(
        "div",
        null,
        React.createElement(
            "strong",
            null,
            "Payment methods"
        )
    );
}

export default function PaymentMethods() {
    return React.createElement(Area, {
        id: "checkout_payment_method_block",
        className: "checkout-payment-methods",
        coreWidgets: [{
            component: Title,
            props: {},
            sort_order: 0,
            id: "payment_method_block_title"
        }]
    });
}