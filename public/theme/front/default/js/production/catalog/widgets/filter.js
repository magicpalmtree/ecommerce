import Area from "../../../../../../../js/production/area.js";
import { ReducerRegistry } from "../../../../../../../js/production/reducer_registry.js";
import { PRODUCT_COLLECTION_FILTER_CHANGED } from "../../../../../../../js/production/action.js";

function Price({ minPrice, maxPrice, maxSteps = 3, minRange = 50, areaProps }) {
    const getSteps = () => {
        let stepNumber = Math.min((parseFloat(maxPrice) - parseFloat(minPrice)) / parseFloat(minRange), maxSteps);
        if (stepNumber <= 1) return [{ from: minPrice, to: maxPrice }];else {
            let step = Math.round((parseFloat(maxPrice) - parseFloat(minPrice)) / stepNumber);
            let steps = [];
            let prev = minPrice;
            for (let i = 0; i < stepNumber; i++) {
                steps.push({ from: prev, to: prev += step });
            }

            return steps;
        }
    };
    // const [steps, setSteps] = React.useState([]);
    //
    // React.useEffect(function() {
    //     setSteps(getSteps());
    // });
    const steps = getSteps();
    return React.createElement(
        "div",
        { className: "row" },
        React.createElement(
            "div",
            { className: "header price-header" },
            React.createElement(
                "div",
                { className: "title" },
                React.createElement(
                    "span",
                    null,
                    "Price"
                )
            ),
            React.createElement(
                "div",
                { className: "filter" },
                steps.map((s, i) => {
                    return React.createElement(
                        "div",
                        { key: i },
                        React.createElement(
                            "a",
                            { href: "#", onClick: e => {
                                    e.preventDefault();areaProps.addFilter('price', 'BETWEEN', `${s.from} AND ${s.to}`);
                                } },
                            React.createElement(
                                "span",
                                null,
                                s.from,
                                " to ",
                                s.to
                            )
                        )
                    );
                })
            )
        )
    );
}

function Attributes({ attributes, areaProps }) {
    const onChange = (e, attributeCode, optionId) => {
        let filter = undefined;
        areaProps.filters.forEach((f, i) => {
            if (f.key === attributeCode) filter = f;
        });
        if (filter === undefined) {
            if (e.target.checked === false) {
                return;
            } else {
                areaProps.addFilter(attributeCode, "IN", [optionId]);
            }
        } else {
            if (e.target.checked === false) {
                areaProps.addFilter(attributeCode, "IN", filter.value.filter(v => {
                    return v !== optionId;
                }));
            } else {
                areaProps.addFilter(attributeCode, "IN", filter.value.concat(optionId));
            }
        }
    };
    return React.createElement(
        "div",
        { className: "filter-attributes" },
        attributes.map((a, i) => {
            return React.createElement(
                "div",
                { key: i },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "strong",
                        null,
                        a.attribute_name
                    )
                ),
                React.createElement(
                    "ul",
                    { className: "uk-list" },
                    a.options.map((o, j) => {
                        return React.createElement(
                            "li",
                            { key: j },
                            React.createElement(
                                "label",
                                null,
                                React.createElement("input", { className: "uk-checkbox", type: "checkbox", onChange: e => onChange(e, a.attribute_code, o.option_id) }),
                                " ",
                                o.option_text
                            )
                        );
                    })
                )
            );
        })
    );
}

function reducer(productCollectionFilter = [], action = {}) {
    if (action.type === PRODUCT_COLLECTION_FILTER_CHANGED) {
        if (action.payload.productCollectionFilter !== undefined) return action.payload.productCollectionFilter;
    }
    return productCollectionFilter;
}

ReducerRegistry.register('productCollectionFilter', reducer);

export default function Filter({ apiUrl }) {
    const dispatch = ReactRedux.useDispatch();
    const rootProductCollectionFilter = ReactRedux.useSelector(state => state.rootProductCollectionFilter);
    const buildQuery = filters => {
        let filterStr = ``;
        filters.forEach((f, i) => {
            let value = f.value;
            if (f.operator == "IN") value = value.join(", ");
            filterStr += `${f.key} : {operator : ${f.operator} value: "${value}"} `;
        });
        filterStr = filterStr.trim();
        if (filterStr) filterStr = `(filter : {${filterStr}})`;

        // TODO: field need to be changeable without overwriting this file
        return `{productFilterTool ${filterStr} {price {minPrice maxPrice } attributes {attribute_name attribute_code options {option_id option_text} } }}`;
    };
    const [data, setData] = React.useState([]);

    React.useLayoutEffect(() => {
        let formData = new FormData();
        formData.append('query', buildQuery(rootProductCollectionFilter));
        axios({
            method: 'post',
            url: apiUrl,
            headers: { 'content-type': 'multipart/form-data' },
            data: formData
        }).then(function (response) {
            if (response.headers['content-type'] !== "application/json") throw new Error('Something wrong, please try again');
            if (_.get(response, 'data.payload.data.productFilterTool')) {
                setData(_.get(response, 'data.payload.data.productFilterTool'));
            }
        }).catch(function (error) {}).finally(function () {
            // e.target.value = null;
            // setUploading(false);
        });
    }, [rootProductCollectionFilter]);

    const [filters, setFilters] = React.useState([]);

    const addFilter = (key, operator, value) => {
        let flag = 0;
        filters.forEach((f, i) => {
            if (f.key === key && (!value || _.isEmpty(value))) flag = 1; // Remove
            if (f.key === key && value && !_.isEmpty(value)) flag = 2; // Update
        });
        if (flag === 0) setFilters(prevFilters => prevFilters.concat({ key: key, operator: operator, value: value }));else if (flag === 1) {
            const newFilters = filters.filter((f, index) => f.key !== key);
            setFilters(newFilters);
        } else setFilters(prevFilters => prevFilters.map((f, i) => {
            if (f.key === key) {
                f.operator = operator;
                f.value = value;
            }
            return f;
        }));
    };

    const cleanFilter = () => {
        setFilters([]);
    };

    const removeFilter = key => {
        setFilters(filters.filter((v, k) => v.key !== key));
    };

    React.useEffect(() => {
        dispatch({ 'type': PRODUCT_COLLECTION_FILTER_CHANGED, 'payload': { 'productCollectionFilter': filters } });
    }, [filters]);

    return React.createElement(Area, {
        id: "category-info",
        addFilter: addFilter,
        filters: filters,
        coreWidgets: [{
            component: Price,
            props: { minPrice: _.get(data, 'price.minPrice', null), maxPrice: _.get(data, 'price.maxPrice', null) },
            sort_order: 10,
            id: "filter-price"
        }, {
            component: Attributes,
            props: { attributes: _.get(data, 'attributes', []) },
            sort_order: 20,
            id: "filter-attributes"
        }]
    });
}