import Area from "../../../../../../../../js/production/area.js";
import Tabs from "./tabs.js";

export default function ProductPageLayout() {
    return <div className="product-detail">
        <Area
            id="product_page_top"
            className="product-page-top"
        />
        <div className="product-page-middle">
            <div className="row">
                <Area
                    id="product_page_middle_left"
                    className="col-12 col-md-5 product-page-middle-left"
                />
                <Area
                    id="product_page_middle_right"
                    className="col-12 col-md-7 product-page-middle-right"
                />
            </div>
        </div>
        <Tabs id={"product_single_tabs"}/>
        <Area
            id="product_page_bottom"
            className="product-page-top"
        />
    </div>
}