<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Catalog\Services;


use function Polavi\get_config;
use function Polavi\resize_image;
use function Polavi\str_replace_last;
use Polavi\Services\Db\Processor;
use function Polavi\the_container;

class ProductMutator
{
    /**@var Processor $this->processor*/
    private $processor;

    public function __construct(Processor $processor = null)
    {
        if ($processor == null)
            $this->processor = the_container()->get(Processor::class);
        else
            $this->processor = $processor;
    }

    public function createProduct(array $data)
    {
        $this->processor->startTransaction();
        try {
            $this->processor->getTable('product')->insert($data);
            $productId = (int) $this->processor->getLastID();
            $data['product_description_product_id'] = $productId;
            $this->processor->getTable('product_description')->insert($data);
            // Save advanced price
            if (isset($data['advance_price']))
                $this->savePrices((int)$productId, $data['advance_price']);

            // Save category
            $categories = isset($data['categories']) ? $data['categories'] : [];
            foreach ($categories as $categoryId)
                $this->processor->getTable('product_category')->insert(['category_id'=>$categoryId, 'product_id'=> $productId]);

            // Save attribute
            if (isset($data['attribute']))
                $this->saveAttribute((int)$productId, $data['attribute']);

            // Save custom option
            if (isset($data['options']))
                $this->saveOptions((int)$productId, $data['options']);
            // Save Images
            $this->saveImages($productId, $data['main_image'] ?? null, $data['images'] ?? []);
            $this->processor->commit();

            return $productId;
        } catch (\Exception $e) {
            $this->processor->rollback();
            throw $e;
        }
    }

    public function updateProduct(int $id, array $data)
    {
        $product = $this->processor->getTable('product')->load($id);
        if ($product == false)
            throw new \RuntimeException('Requested product does not exist');
        $this->processor->startTransaction();
        try {
            $this->processor->getTable('product')->where('product_id', '=', $id)->update($data);
            $data['product_description_product_id'] = $id;

            $this->processor->getTable('product_description')
                ->insertOnUpdate($data);

            // Save advanced price
            if (isset($data['advance_price']))
                $this->savePrices((int)$id, $data['advance_price']);

            // Save category
            $categories = isset($data['categories']) ? $data['categories'] : [];
            $this->processor->getTable('product_category')
                ->where('product_id', '=', $id)
                ->delete();
            foreach ($categories as $categoryId)
                $this->processor->getTable('product_category')->insert(['category_id'=>$categoryId, 'product_id'=> $id]);

            // Save attribute
            if (isset($data['attribute']))
                $this->saveAttribute((int)$id, $data['attribute']);

            // Save custom option
            if (isset($data['options']))
                $this->saveOptions((int)$id, $data['options']);

            // Save Images
            $this->saveImages($id, $data['main_image'] ?? null, $data['images'] ?? []);
            $this->processor->commit();

            return $id;
        } catch (\Exception $e) {
            $this->processor->rollback();
            throw $e;
        }
    }

    protected function saveOptions(int $productId, array $options)
    {
        if (empty($options)) {
            $this->processor->getTable('product_custom_option')
                ->where('product_custom_option_product_id', '=', $productId)
                ->delete();
            return;
        }
        $oldOptions = [];
        $optionTable = $this->processor->getTable('product_custom_option')
            ->where('product_custom_option_product_id', '=', $productId);
        while ($row = $optionTable->fetch()) {
            $oldOptions[$row['product_custom_option_id']] = $row['product_custom_option_id'];
        }
        foreach ($oldOptions as $oId)
            if (!array_key_exists($oId, $options))
                $this->processor->getTable('product_custom_option')
                    ->where('product_custom_option_id', '=', $oId)
                    ->delete();
        foreach ($options as $key=>$option) {
            $optionData = [
                'product_custom_option_product_id'  => $productId,
                'option_name' => trim($option['option_name']),
                'option_type' => $option['option_type'],
                'is_required' => $option['is_required'],
                'sort_order'  => $option['sort_order']
            ];
            $optionId = null;
            if (!array_key_exists($key, $oldOptions))
                $this->processor->getTable('product_custom_option')->insert($optionData);
            else {
                $optionId = $key;
                $this->processor->getTable('product_custom_option')
                    ->where('product_custom_option_id', '=', $key)
                    ->update($optionData);
            }
            $values = isset($option['values']) ? $option['values'] : [];
            $optionId = $optionId == null ? $this->processor->getLastID() : $optionId;
            $this->saveOptionValue((int)$optionId, $values);
        }
    }

    protected function saveOptionValue(int $optionId, array $values)
    {
        $oldValues = [];
        $valueTable = $this->processor->getTable('product_custom_option_value')
            ->where('option_id', '=', $optionId);
        while ($row = $valueTable->fetch()) {
            $oldValues[$row['product_custom_option_value_id']] = $row['product_custom_option_value_id'];
        }
        foreach ($oldValues as $oId)
            if (!array_key_exists($oId, $values))
                $this->processor->getTable('product_custom_option_value')
                    ->where('product_custom_option_value_id', '=', $oId)
                    ->delete();
        foreach ($values as $key=>$value) {
            $valueData = [
                'option_id'  => $optionId,
                'extra_price' => floatval($value['extra_price']),
                'sort_order' => (int)$value['sort_order'],
                'value' => $value['value']
            ];
            if (!array_key_exists($key, $oldValues))
                $this->processor->getTable('product_custom_option_value')->insert($valueData);
            else {
                $this->processor->getTable('product_custom_option_value')
                    ->where('product_custom_option_value_id', '=', $key)
                    ->update($valueData);
            }
        }
    }

    protected function saveAttribute(int $productId, array $attributes)
    {
        foreach ($attributes as $key=>$value) {
            $attribute = $this->processor->getTable('attribute')->loadByField('attribute_code', $key);
            if ($attribute === false)
                continue;
            $attributeData = [
                'product_id' => $productId,
                'attribute_id' => $attribute['attribute_id'],
            ];
            if ($attribute['type'] == 'textarea' || $attribute['type'] == 'text') {
                $flag = $this->processor->getTable("product_attribute_value_index")
                    ->where("product_id", "=", $productId)
                    ->andWhere("attribute_id", "=", $attribute['attribute_id'])
                    ->fetchOneAssoc();

                if ($flag)
                    $this->processor->getTable('product_attribute_value_index')
                        ->where("product_id", "=", $productId)
                        ->andWhere("attribute_id", "=", $attribute['attribute_id'])
                        ->update(['attribute_value_text'=> trim($value)]);
                else {
                    $this->processor->getTable('product_attribute_value_index')
                        ->insert($attributeData + ['attribute_value_text'=> trim($value)]);
                }
            } else if ($attribute['type'] == 'multiselect') {
                foreach ($value as $val) {
                    $option = $this->processor->getTable('attribute_option')->load((int)$val);
                    if ($option === false)
                        continue;
                    $this->processor->getTable('product_attribute_value_index')->insertOnUpdate(
                        $attributeData + ['attribute_value_text'=> $option['option_text'], 'option_id'=>(int)$val]
                    );
                }
            } else if ($attribute['type'] == 'select') {
                $option = $this->processor->getTable('attribute_option')->load((int)$value);
                if ($option === false)
                    continue;
                $this->processor->getTable('product_attribute_value_index')->insertOnUpdate(
                    $attributeData + ['attribute_value_text'=> $option['option_text'], 'option_id'=>(int)$value]
                );
            } else {
                $this->processor->getTable('product_attribute_value_index')->insertOnUpdate(
                    $attributeData + ['attribute_value_text'=> $value]
                );
            }
        }
    }

    protected function savePrices(int $productId, array $prices)
    {
        foreach ($prices as $key=>$price) {
            $price['product_price_product_id'] = $productId;
            $this->processor->getTable('product_price')
                ->insertOnUpdate($price);
        }
    }

    protected function saveImages($productId, $mainImage = null, array $gallery = []) {
        $this->processor->getTable('product_image')->where('product_image_product_id', '=', $productId)->delete();
        $flag = false;
        if ($mainImage)
            $this->processor->getTable('product')->where('product_id', '=', $productId)->update(['image'=>$mainImage]);
        else if ($gallery) {
            $flag = true;
            $this->processor->getTable('product')->where('product_id', '=', $productId)->update(['image'=>array_values($gallery)[0]]);
        }
        if ($flag == true)
            array_shift($gallery);

        foreach ($gallery as $image) {
            $this->processor->getTable('product_image')->insert([
                'product_image_product_id' => $productId,
                'image'=> $image
            ]);
        }
        // Resize images
        if ($flag == true)
            $this->processImages($gallery);
        else
            $this->processImages(array_merge($gallery,[$mainImage]));
    }

    protected function processImages($images = [])
    {
        foreach ($images as $image) {
            $path = MEDIA_PATH . DS . $image;
            try {
                resize_image(
                    $path,
                    (int) get_config('catalog_product_list_image_width', 250),
                    (int) get_config('catalog_product_list_image_height', 250)
                )->save(str_replace_last('.', '_list.', $path));
                resize_image(
                    $path,
                    (int) get_config('thumb_image_size_width', 80),
                    (int) get_config('thumb_image_size_height', 80)
                )->save(str_replace_last('.', '_thumb.', $path));
                resize_image(
                    $path,
                    (int) get_config('catalog_product_detail_image_width', 500),
                    (int) get_config('catalog_product_detail_image_height', 500)
                )->save(str_replace_last('.', '_main.', $path));

            } catch (\Exception $e) {
                // TODO: write a log message here
            }
        }

        return true;
    }
}