import {Fetch} from "../../../../../../js/production/fetch.js";

export default function FlatRate(props) {
    const shippingAddress = ReactRedux.useSelector(state => _.get(state, 'appState.cart.shippingAddress'));
    const shippingMethod = ReactRedux.useSelector(state => _.get(state, 'appState.cart.shippingMethod'));
    const allowCountries = _.get(props, 'countries');

    const onChange = (e) => {
        e.preventDefault();
        Fetch(
            props.apiUrl,
            false,
            "POST",
            {method_code: "flat_rate", method_name: _.get(props, 'label', 'Flat rate')}
        );
    };

    if((allowCountries.length === 0) || allowCountries.indexOf(_.get(shippingAddress, 'country')) !== -1) {
        props.areaProps.setNoMethod(false);
        return <div className="shipping-method flat-rate">
            <label htmlFor={"free-shipping"}>
                <input type={"radio"} className="uk-radio" checked={shippingMethod === 'flat_rate'} onChange={(e)=>onChange(e)}/>
                <span className="pl-2">{_.get(props, 'label', 'Flat rate')} - {_.get(props, 'fee')}</span>
            </label>
        </div>;
    } else
        return null;
}