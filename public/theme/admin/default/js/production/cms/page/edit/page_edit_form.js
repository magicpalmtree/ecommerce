var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

import { Form } from "../../../../../../../../js/production/form/form.js";
import Area from "../../../../../../../../js/production/area.js";
import Text from "../../../../../../../../js/production/form/fields/text.js";
import Tinycme from "../../../../../../../../js/production/form/fields/ckeditor.js";
import Select from "../../../../../../../../js/production/form/fields/select.js";
import { ADD_ALERT } from "../../../../../../../../js/production/event-types.js";
import A from "../../../../../../../../js/production/a.js";
import Switch from "../../../../../../../../js/production/form/fields/switch.js";
import { Fetch } from "../../../../../../../../js/production/fetch.js";
import TextArea from "../../../../../../../../js/production/form/fields/textarea.js";

function General({ pageId, name, content, layout, status }) {
    return React.createElement(
        "div",
        { className: "sml-block" },
        React.createElement(
            "div",
            { className: "sml-block-title" },
            "General information"
        ),
        React.createElement(Area, { id: "admin-page-edit-general", coreWidgets: [{
                component: Text,
                props: { id: 'name', value: name, formId: "page-edit-form", name: "variables[page][name]", label: "Name", validation_rules: ["notEmpty"] },
                sort_order: 10,
                id: "name"
            }, {
                component: Tinycme,
                props: { id: 'content', value: content, formId: "page-edit-form", name: "variables[page][content]", label: "Content" },
                sort_order: 20,
                id: "content"
            }, {
                component: Switch,
                props: { id: 'status', value: status, formId: "page-edit-form", name: "variables[page][status]", type: "select", label: "Status", isTranslateAble: false },
                sort_order: 30,
                id: "status"
            }, {
                component: Select,
                props: { id: 'layout', value: layout, formId: "page-edit-form", name: "variables[page][layout]", label: "Status", options: [{ value: 'oneColumn', text: 'One column' }, { value: 'twoColumnLeft', text: 'Two columns left' }, { value: 'twoColumnRight', text: 'Two columns right' }, { value: 'threeColumn', text: 'Three columns' }], isTranslateAble: false },
                sort_order: 40,
                id: "layout"
            }] }),
        pageId && React.createElement("input", { readOnly: true, type: "text", name: "variables[page][id]", value: pageId, style: {
                display: 'none'
            } })
    );
}

function SEO({ url_key, meta_keywords, meta_title, meta_description }) {
    return React.createElement(
        "div",
        { className: "sml-block" },
        React.createElement(
            "div",
            { className: "sml-block-title" },
            "Seo"
        ),
        React.createElement(Area, {
            id: "admin-page-edit-seo",
            coreWidgets: [{
                component: Text,
                props: { id: 'url_key', value: url_key, formId: "page-edit-form", name: "variables[page][url_key]", label: "Url key", validation_rules: ["notEmpty"] },
                sort_order: 10,
                id: "seo_key"
            }, {
                component: Text,
                props: { id: 'meta_keywords', value: meta_keywords, formId: "page-edit-form", name: "variables[page][meta_keywords]", label: "Meta keywords" },
                sort_order: 20,
                id: "meta_keywords"
            }, {
                component: Text,
                props: { id: 'meta_title', value: meta_title, formId: "page-edit-form", name: "variables[page][meta_title]", label: "Meta title" },
                sort_order: 30,
                id: "meta_title"
            }, {
                component: TextArea,
                props: { id: 'meta_description', value: meta_description, formId: "page-edit-form", name: "variables[page][meta_description]", label: "Meta description" },
                sort_order: 40,
                id: "meta_description"
            }] })
    );
}
export default function PageEditFormComponent(props) {
    const dispatch = ReactRedux.useDispatch();
    const onComplete = response => {
        if (_.get(response, 'payload.data.createCmsPage.status') === true) {
            dispatch({ 'type': ADD_ALERT, 'payload': { alerts: [{ id: "cms_page_update_success", message: 'Page has been saved successfully', type: "success" }] } });
            Fetch(props.listUrl, true);
        } else dispatch({ 'type': ADD_ALERT, 'payload': { alerts: [{ id: "cms_page_update_error", message: _.get(response, 'payload.data.createCmsPage.message', 'Something wrong, please try again'), type: "error" }] } });
    };
    return React.createElement(
        "div",
        null,
        React.createElement(
            Form,
            _extends({
                id: "page-edit-form",
                submitText: null,
                onComplete: onComplete
            }, props),
            React.createElement("input", { type: "text", name: "query", value: "mutation CreateCMSPage($page: CmsPageInput!) { createCmsPage (page: $page) {status message}}", readOnly: true, style: { display: 'none' } }),
            React.createElement(
                "div",
                { className: "form-head sticky" },
                React.createElement(
                    "div",
                    { className: "child-align-middle" },
                    React.createElement(
                        A,
                        { url: props.listUrl, className: "" },
                        React.createElement("i", { className: "fas fa-arrow-left" }),
                        React.createElement(
                            "span",
                            { className: "pl-1" },
                            "CMS pages"
                        )
                    )
                ),
                React.createElement(
                    "div",
                    { className: "buttons" },
                    React.createElement(
                        A,
                        { className: "btn btn-danger", url: props.cancelUrl },
                        "Cancel"
                    ),
                    React.createElement(
                        "button",
                        { type: "submit", className: "btn btn-primary" },
                        "Submit"
                    )
                )
            ),
            React.createElement(
                "div",
                { className: "row" },
                React.createElement(Area, {
                    id: "admin_page_edit_form_left",
                    className: "col-8",
                    coreWidgets: [{
                        component: General,
                        props: _extends({}, props),
                        sort_order: 10,
                        id: "page_edit_general"
                    }] }),
                React.createElement(Area, {
                    id: "admin_page_edit_form_right",
                    className: "col-4",
                    coreWidgets: [{
                        component: SEO,
                        props: _extends({}, props),
                        sort_order: 10,
                        id: "page_edit_seo"
                    }] })
            )
        )
    );
}