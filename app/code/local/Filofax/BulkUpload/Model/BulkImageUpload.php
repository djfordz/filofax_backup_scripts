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
                    foreach ($paths as $path) {
                        $img = $this->splitArray($path);
                        if (file_exists($img['path'])) {
                            $product->addImageToMediaGallery($img['path'], $img['options'], true, false);
                        } 
                    }
                    

                    if (!empty($img['label'])) {
                        $this->addLabel($product, $img['label']);
                    }

                    $product->save();
                }
            }
        }
        echo json_encode(array("response" => "true"));
    }

    protected function addLabel($product, $label) {
        foreach ($product->getData('media_gallery') as $each) {
            foreach ($each as $image) {
                $attributes = $product
                    ->getTypeInstance(true)
                    ->getSetAttributes($product);
                $attributes['media_gallery']
                    ->getBackend()
                    ->updateImage($product, $image['file'], array('label' => $label));
            }
        }

    }

    protected function splitArray($var)
    {
        $options = array();
        $label = null;

        if (empty($var)) {
            return;
        }

        if (is_array($var)) {
            $this->splitArray($var);
        } else {
            preg_match('/(_\d)(_\d)(_\d)(?:(_\w*))/', $var, $imgOptions);

            if (count($imgOptions) > 0) {
                for($i = 0; $i <= count($imgOptions); $i++) {
                    switch ($i) {
                        case 1: $imgOptions[$i] == '_1' ? array_push($options, 'image') : array_push($options, '');
                        break;
                        case 2: $imgOptions[$i] == '_1' ? array_push($options, 'small_image') : array_push($options, '');
                        break;
                        case 3: $imgOptions[$i] == '_1' ? array_push($options, 'thumbnail') : array_push($options, '');
                        break;
                        case 4: $label = isset($imgOptions[$i]) ? substr($imgOptions[$i], 1) : null;
                        break;
                    }
                }
            }

            return array('path' => $var, 'options' => $options, 'label' => $label);
        }
    }

    protected function getImagePath($pattern) {
        return glob($this->_path . DS . $pattern . '*.jpg');
    }
}
