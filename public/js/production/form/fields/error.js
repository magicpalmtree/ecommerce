let Error = props => {
    let { error } = props;
    if (!error) return "";else return React.createElement(
        "div",
        { className: "field-validation-error" },
        React.createElement(
            "span",
            null,
            error
        )
    );
};

export { Error };