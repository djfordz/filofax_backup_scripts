<?php

class Filofax_BulkUpload_Model_BulkImageUpload extends Mage_Core_Model_Abstract
{
    const XML_IMAGE_PATH = 'import';

    protected $_galleryBackendModel;

    protected $_path;

    public function __construct()
    {
        $this->_path = Mage::getBaseDir('media') . DS . self::XML_IMAGE_PATH;
    }

    public function execute()
    {
        $products = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect(array('sku'))
            ->load(); 

        if ($products->count() > 0) {
            foreach ($products as $product) {
                $sku = $product->getSku();
                $paths = $this->getImagePath($sku);
                $i = 0;
                if (count($paths) > 0) {
                    $skuPaths = array();
                    foreach ($paths as $path) {
                        
                        $pathParts = pathinfo($path);
                        preg_match('/([-\d\w]*)__/', $pathParts['filename'], $skuPaths);
                        if ($skuPaths[1] == $sku) {
                            $img = $this->splitArray($path);
                            $parentProduct = $this->getParentProduct($product);
                            if (isset($parentProduct) && $img['front'] == true) {
                                if ($img['config_value'] == true) {
                                    $parentTypes = array('image', 'small_image', 'thumbnail');
                                    $parentSort = 0;
                                } else {
                                    $parentTypes = array();
                                    if ($i == 0) {
                                        $parentSort = 1;
                                    } else {
                                        $parentSort = $i;
                                    }
                                    
                                }
                                $path_parts = pathinfo($img['path']);
                                $parentImgPath = $path_parts['dirname'] . '/' . $path_parts['filename'] . '-1.' . $path_parts['extension'];
                                $this->addImage($parentProduct, $parentImgPath, $parentTypes, $img['label'], $parentSort);
                                
                            }
                            $this->addImage($product, $img['path'], $img['types'], $img['label'], $img['sort_order']);
                            $i++;
                            $parentProduct->save();
                        }
                    }
                    
                    if ($skuPaths[1] == $sku) {
                        $product->save();
                    }
                    
                }
            }
        }
        echo json_encode(array("response" => "true"));
    }

    protected function getParentProduct($product)
    {
        $parent = null;
        if ($product->getTypeId() == "simple") {
            $parentIds = Mage::getModel('catalog/product_type_grouped')
                ->getParentIdsByChild($product->getId());
            if (!$parentIds) {
                $parentIds = Mage::getModel('catalog/product_type_configurable')
                    ->getParentIdsByChild($product->getId());
            }

            if (isset($parentIds[0])) {
                $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
            }
        }
        
        return $parent;
    }

    protected function addImage($product, $file, $types, $label, $sortOrder) {

        try {
            $attributes = $product
                ->getTypeInstance(true)
                ->getSetAttributes($product);
            $filename = $attributes['media_gallery']
                ->getBackend()
                ->addImage($product, $file, $types, true, false);
            $attributes['media_gallery']
                ->getBackend()
                ->updateImage($product, $filename, array('label' => $label, 'position' => $sortOrder));
        } catch (Exception $e) {
            Mage::logException($e);
        }
        
    }

    protected function splitArray($var)
    {
        $types = array();
        $label = null;
        $sortOrder = null;
        $order = null;;
        $configVal = null;
        $front = null;

        if (empty($var)) {
            return;
        }

        if (is_array($var)) {
            $this->splitArray($var);
        } else {

            preg_match_all('/__([a-zA-Z-1]+)?_?([a-zA-Z]+[\ -]?[a-zA-Z]+[-\ \:]?[a-zA-Z]+)?_?([\d])?_?([\d])?_?([\d])?_?([\d])?/', $var, $imgOptions);

            if (count($imgOptions) > 0) {
                for ($i = 0; $i <= count($imgOptions) - 1; $i++) {
                    switch ($i) {
                    case 1: $order = isset($imgOptions[$i][0]) ? $imgOptions[$i][0] : '';
                        break;
                    case 2: $label = isset($imgOptions[$i][0]) ? $imgOptions[$i][0] : '';
                        break;
                    case 3: $sortOrder = isset($imgOptions[$i][0]) ? substr($imgOptions[$i][0], 1) : '';
                        break;
                    case 4: $imgOptions[$i][0] == '1' ? array_push($types, 'image') : array_push($types, '');
                        break;
                    case 5: $imgOptions[$i][0] == '1' ? array_push($types, 'small_image') : array_push($types, '');
                        break;
                    case 6: $imgOptions[$i][0] == '1' ? array_push($types, 'thumbnail') : array_push($types, '');
                        break;
                    }
                }
            }

            if (!empty($order)) {
                $orderArr = $this->_checkOrder($order);
                $sortOrder = $orderArr['sort_order'];
                $configVal = $orderArr['config_value'];
                $front = $orderArr['front'];
                $types = $orderArr['types'];
                if ($front) {
                    $pathParts = pathinfo($var);
                    if (!copy($var, $pathParts['dirname'] . '/' . $pathParts['filename'] . '-1.' . $pathParts['extension'])) {
                        Mage::log('failed to copy file', null, 'test.log');
                    }
                }
            }

            if (strpos($label, ':')) {
                $label = str_replace(':', '/', $label);
            }

            return array('path' => $var, 'types' => $types, 'label' => $label, 'sort_order' => $sortOrder, 'config_value' => $configVal, 'front' => $front);
        }
    }

    protected function _checkOrder($name)
    {
        $sortOrder = '';
        $configVal = false;
        $front = false;
        $types = null;

        $name = strtolower($name);

        switch ($name) {
            case 'front': $sortOrder = 0; $front = true; $types = array('image', 'small_image', 'thumbnail');
                break;
            case 'iso': $sortOrder = 1;
                break;
            case 'open': $sortOrder = 2;
                break;
            case 'inside' : $sortOrder = 3;
                break;
            case 'front-1' : $sortOrder = 0; $configVal = true; $front = true; $types = array('image', 'small_image', 'thumbnail');
                break;
        }

        return array('sort_order' => $sortOrder, 'config_value' => $configVal, 'front' => $front, 'types' => $types);
    }

    protected function getImagePath($pattern) {
        return glob($this->_path . DS . $pattern . '*.jpg');
    }
}
