import Area from "../../../../../../../../js/production/area.js";

const Name = ({ name }) => {
    return React.createElement(
        "h1",
        { className: "category-name" },
        name
    );
};

const Description = ({ description }) => {
    return React.createElement("div", { className: "category-description", dangerouslySetInnerHTML: { __html: description } });
};

export default function CategoryInfo(props) {
    return React.createElement(
        "div",
        { className: "container" },
        React.createElement(Area, {
            id: "category-general-info",
            className: "category-general-info",
            coreWidgets: [{
                component: Name,
                props: { name: props.name },
                sort_order: 10,
                id: "category-name"
            }, {
                component: Description,
                props: { description: props.description },
                sort_order: 20,
                id: "category-description"
            }]
        })
    );
}