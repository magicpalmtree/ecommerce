import { Form } from "../../../../../../../../js/production/form/form.js";
import Text from "../../../../../../../../js/production/form/fields/text.js";
import Password from "../../../../../../../../js/production/form/fields/password.js";
import { ADD_APP_STATE } from "../../../../../../../../js/dev/event-types.js";
import { Fetch } from "../../../../../../../../js/production/fetch.js";

function DBInfo() {
    return React.createElement(
        "div",
        { className: "col-6" },
        React.createElement(
            "div",
            null,
            React.createElement(
                "strong",
                null,
                "Database information"
            )
        ),
        React.createElement(
            "p",
            null,
            "Please provide your database connection information."
        ),
        React.createElement(
            "div",
            null,
            React.createElement(Text, {
                formId: "installation-form",
                name: "db_name",
                label: "Database name",
                value: "",
                validation_rules: ['notEmpty']
            }),
            React.createElement(Text, {
                formId: "installation-form",
                name: "db_user",
                label: "Database user",
                value: "",
                validation_rules: ['notEmpty']
            }),
            React.createElement(Text, {
                formId: "installation-form",
                name: "db_password",
                label: "Database password",
                value: ""
            }),
            React.createElement(Text, {
                formId: "installation-form",
                name: "db_host",
                label: "Database host",
                value: "localhost",
                validation_rules: ['notEmpty']
            })
        )
    );
}

function AdminUser() {
    return React.createElement(
        "div",
        { className: "col-6" },
        React.createElement(
            "div",
            null,
            React.createElement(
                "strong",
                null,
                "Admin user information"
            )
        ),
        React.createElement(
            "div",
            null,
            React.createElement(Text, {
                formId: "installation-form",
                name: "full_name",
                label: "Full name",
                value: "",
                validation_rules: ['notEmpty']
            }),
            React.createElement(Text, {
                formId: "installation-form",
                name: "email",
                label: "Email",
                value: "",
                validation_rules: ['notEmpty', 'email']
            }),
            React.createElement(Password, {
                formId: "installation-form",
                name: "password",
                label: "Password",
                value: "",
                validation_rules: ['notEmpty']
            })
        )
    );
}

function Welcome() {
    const admin = ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin'));
    const front = ReactRedux.useSelector(state => _.get(state, 'appState.baseUrl'));
    return React.createElement(
        "div",
        null,
        React.createElement(
            "h2",
            { className: "text-center mb-4" },
            "Great. Let's start using Polavi"
        ),
        React.createElement(
            "div",
            { className: "text-center" },
            React.createElement(
                "p",
                null,
                React.createElement(
                    "a",
                    { href: admin, className: "btn btn-primary", target: "_blank" },
                    "Admin"
                )
            ),
            React.createElement(
                "a",
                { href: front, className: "btn btn-primary", target: "_blank" },
                "Front site"
            )
        )
    );
}

export default function Installation({ action }) {
    const letsGo = ReactRedux.useSelector(state => _.get(state, 'appState.letsGo'));
    const dispatch = ReactRedux.useDispatch();
    const [ready, setReady] = React.useState(false);
    const [stack, setStack] = React.useState([{
        step: 'Basic setting',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Setting'),
        running: false,
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Cms module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Cms'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Customer module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Customer'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'User module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/User'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Tax module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Tax'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Catalog setting',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Catalog'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Checkout setting',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Checkout'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Order setting',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Order'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'FlatRate module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/FlatRate'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Cod module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Cod'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Discount setting',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Discount'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Graphql module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Graphql'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'SendGrid module',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/SendGrid'),
        status: undefined,
        message: 'Waiting'
    }, {
        step: 'Finishing',
        api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/install/finish'),
        status: undefined,
        message: 'Waiting'
    }]);

    React.useEffect(() => {
        if (letsGo !== true) return;
        for (let i = 0; i < stack.length; ++i) {
            let item = stack[i];
            if (item.message === 'Running') break;
            if (item.status === false) break;
            if (item.status === undefined) {
                Fetch(item.api, false, 'POST', {}, () => {
                    setStack(stack.map(s => {
                        if (s.step === item.step) s.message = 'Running';

                        return s;
                    }));
                }, response => {
                    if (parseInt(response.success) === 1) setStack(stack.map(s => {
                        if (s.step === item.step) {
                            s.message = 'Done';
                            s.status = true;
                            if (s.step === 'Finishing') setReady(true);
                        }
                        return s;
                    }));else setStack(stack.map(s => {
                        if (s.step === item.step) {
                            s.message = response.message;
                            s.status = false;
                        }
                        return s;
                    }));
                });
                break;
            }
        }
    });
    return React.createElement(
        "div",
        null,
        React.createElement(
            "div",
            { className: "text-center mb-5" },
            React.createElement(
                "h3",
                null,
                "Welcome to Polavi"
            )
        ),
        letsGo !== true && letsGo !== undefined && React.createElement(
            "div",
            { className: "text-danger" },
            letsGo
        ),
        letsGo !== true && React.createElement(
            Form,
            {
                id: "installation-form",
                submitText: "Let's go",
                action: action,
                onComplete: response => {
                    if (response.success === 1) dispatch({ 'type': ADD_APP_STATE, 'payload': { appState: { letsGo: true } } });else dispatch({ 'type': ADD_APP_STATE, 'payload': { appState: { letsGo: _.get(response, 'message', 'Something wrong. Please check again information') } } });
                }
            },
            React.createElement(
                "div",
                { className: "row" },
                React.createElement(DBInfo, null),
                React.createElement(AdminUser, null)
            )
        ),
        letsGo === true && React.createElement(
            "ul",
            { className: "installation-stack list-basic text-center" },
            stack.map((s, i) => {
                return React.createElement(
                    "li",
                    { key: i },
                    React.createElement(
                        "span",
                        null,
                        s.step,
                        " "
                    ),
                    s.status === undefined && React.createElement(
                        "span",
                        null,
                        s.message
                    ),
                    s.status === true && React.createElement(
                        "span",
                        { className: "text-success" },
                        s.message
                    ),
                    s.status === false && React.createElement(
                        "span",
                        { className: "text-danger" },
                        s.message
                    )
                );
            })
        ),
        ready === true && React.createElement(Welcome, null)
    );
}