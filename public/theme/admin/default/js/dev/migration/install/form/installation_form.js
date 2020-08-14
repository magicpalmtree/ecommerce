import {Form} from "../../../../../../../../js/production/form/form.js";
import Text from "../../../../../../../../js/production/form/fields/text.js";
import Password from "../../../../../../../../js/production/form/fields/password.js";
import {ADD_APP_STATE} from "../../../../../../../../js/dev/event-types.js";
import {Fetch} from "../../../../../../../../js/production/fetch.js";

function DBInfo() {
    return <div className="col-6">
        <div><strong>Database information</strong></div>
        <p>Please provide your database connection information.</p>
        <div>
            <Text
                formId = "installation-form"
                name = "db_name"
                label = "Database name"
                value = ''
                validation_rules = {['notEmpty']}
            />
            <Text
                formId = "installation-form"
                name = "db_user"
                label = "Database user"
                value = ''
                validation_rules = {['notEmpty']}
            />
            <Text
                formId = "installation-form"
                name = "db_password"
                label= "Database password"
                value= ''
            />
            <Text
                formId = "installation-form"
                name = "db_host"
                label= "Database host"
                value= 'localhost'
                validation_rules= {['notEmpty']}
            />
        </div>
    </div>
}

function AdminUser() {
    return <div className="col-6">
        <div><strong>Admin user information</strong></div>
        <div>
            <Text
                formId = "installation-form"
                name = "full_name"
                label = "Full name"
                value = ''
                validation_rules= {['notEmpty']}
            />
            <Text
                formId = "installation-form"
                name = "email"
                label = "Email"
                value = ''
                validation_rules= {['notEmpty', 'email']}
            />
            <Password
                formId = "installation-form"
                name = "password"
                label = "Password"
                value = ''
                validation_rules= {['notEmpty']}
            />
        </div>
    </div>
}

function Welcome() {
    const admin = ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin'));
    const front = ReactRedux.useSelector(state => _.get(state, 'appState.baseUrl'));
    return <div>
        <h2 className={"text-center mb-4"}>Great. Let's start using Polavi</h2>
        <div className="text-center">
            <p><a href={admin} className="btn btn-primary" target='_blank'>Admin</a></p>
            <a href={front} className="btn btn-primary" target='_blank'>Front site</a>
        </div>
    </div>
}

export default function Installation({action}) {
    const letsGo = ReactRedux.useSelector(state => _.get(state, 'appState.letsGo'));
    const dispatch = ReactRedux.useDispatch();
    const [ready, setReady] = React.useState(false);
    const [stack, setStack] = React.useState(
        [
            {
                step: 'Basic setting',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Setting'),
                running: false,
                status: undefined,
                message: 'Waiting',
            },
            {
                step: 'Cms module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Cms'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Customer module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Customer'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'User module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/User'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Tax module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Tax'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Catalog setting',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Catalog'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Checkout setting',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Checkout'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Order setting',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Order'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'FlatRate module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/FlatRate'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Cod module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Cod'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Discount setting',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Discount'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Graphql module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/Graphql'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'SendGrid module',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/migration/module/install/SendGrid'),
                status: undefined,
                message: 'Waiting'
            },
            {
                step: 'Finishing',
                api: ReactRedux.useSelector(state => _.get(state, 'appState.baseUrlAdmin') + '/install/finish'),
                status: undefined,
                message: 'Waiting'
            }
        ]
    );

    React.useEffect(()=>{
        if(letsGo !== true)
            return;
        for (let i = 0; i < stack.length; ++i) {
            let item = stack[i];
            if(item.message === 'Running')
                break;
            if(item.status === false)
                break;
            if(item.status === undefined) {
                Fetch(item.api, false, 'POST', {}, ()=> {
                    setStack(stack.map((s)=> {
                        if(s.step === item.step)
                            s.message = 'Running';

                        return s;
                    }));
                }, (response) => {
                    if(parseInt(response.success) === 1)
                        setStack(stack.map((s)=> {
                            if(s.step === item.step) {
                                s.message = 'Done';
                                s.status = true;
                                if(s.step === 'Finishing')
                                    setReady(true);
                            }
                            return s;
                        }));
                    else
                        setStack(stack.map((s)=> {
                            if(s.step === item.step) {
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
    return <div>
        <div className="text-center mb-5"><h3>Welcome to Polavi</h3></div>
        {(letsGo !== true && letsGo !== undefined) && <div className="text-danger">{letsGo}</div>}
        {letsGo !== true && <Form
            id = "installation-form"
            submitText="Let's go"
            action = {action}
            onComplete={(response)=> {
                if(response.success === 1)
                    dispatch({'type': ADD_APP_STATE, 'payload': {appState: {letsGo: true}}});
                else
                    dispatch({'type': ADD_APP_STATE, 'payload': {appState: {letsGo: _.get(response, 'message', 'Something wrong. Please check again information')}}});
            }}
        >
            <div className="row">
                <DBInfo/>
                <AdminUser/>
            </div>
        </Form>}
        {letsGo === true && <ul className="installation-stack list-basic text-center">
            {stack.map((s, i)=> {
                return <li key={i}>
                    <span>{s.step} </span>
                    {s.status === undefined && <span>{s.message}</span>}
                    {s.status === true && <span className="text-success">{s.message}</span>}
                    {s.status === false && <span className="text-danger">{s.message}</span>}
                </li>
            })}
        </ul>}
        {ready === true && <Welcome/>}
    </div>
}