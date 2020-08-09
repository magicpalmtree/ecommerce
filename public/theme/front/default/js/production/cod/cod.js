import { Fetch } from "../../../../../../js/production/fetch.js";

export default function Cod(props) {
    const cartTotal = ReactRedux.useSelector(state => _.get(state, 'appState.cart.subTotal', 0));
    const paymentMethod = ReactRedux.useSelector(state => _.get(state, 'appState.cart.paymentMethod'));

    let status = parseInt(_.get(props, 'status'));
    let label = _.get(props, 'label');
    let min = parseFloat(_.get(props, 'minTotal'));
    let max = parseFloat(_.get(props, 'maxTotal'));
    if (status === 0 || min > cartTotal || max < cartTotal) return null;

    const onChange = e => {
        e.preventDefault();
        Fetch(props.apiUrl, false, "POST", { method_code: "cod", method_name: _.get(props, 'label', 'Cash on delivery') });
    };

    return React.createElement(
        'div',
        null,
        React.createElement(
            'label',
            { htmlFor: "cod-payment-method" },
            React.createElement('input', {
                type: "radio",
                name: "payment_method",
                id: "cod-payment-method",
                value: "cod",
                className: 'uk-radio',
                onChange: e => onChange(e),
                checked: paymentMethod === 'cod'
            }),
            React.createElement(
                'span',
                { className: 'pl-2' },
                label
            )
        )
    );
}