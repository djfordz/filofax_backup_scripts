<?php

class Filofax_Collection_Block_ProductCollection extends Mage_Core_Block_Template
{
    public function getProductCollection($categoryId)
    {
        $category = Mage::getModel('catalog/category')->load($categoryId );
        $children = Mage::getModel('catalog/category')
            ->getCollection()
            ->setStoreId(Mage::app()->getStore()->getId());

        return $children->addAttributeToSelect('*')
            ->addAttributeToFilter('parent_id', $category->getId())
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSort('name');
    }

    protected function _resizeImage($img)
    {

        $imagePath = Mage::getBaseDir ('media') . DS . "catalog" . DS . "category" . DS . $img;
        if (!is_file( $imagePath )) {
            return false;
        }
            
        $resizedPath = Mage::getBaseDir( 'media' ) . DS . "catalog" . DS . "product" . DS . "cache" .DS. "category" .DS. "resized" . DS . $img;

        try {
            $image = new Varien_Image($imagePath);
            $image->constrainOnly(false);
            $image->keepFrame(true);
            $image->backgroundColor(array(255,255,255));
            $image->keepAspectRatio(true);
            $image->resize(250, 250);
            $image->save($resizedPath);
        } catch (Mage_Core_Exception $e) {
            Mage::logException('cannot resize image' . $e->getMessage());
        }

        $storeCode = Mage::app()->getStore()->getCode();
        $baseUrl = str_replace("$storeCode/", '', $this->getBaseUrl());

        return $baseUrl . DS . 'media' . DS . 'catalog' . DS . 'product' . DS . 'cache' . DS . 'category' . DS . 'resized' . DS . $img;

    }

    public function resizeImageUrl($img)
    {
        $storeCode = Mage::app()->getStore()->getCode();
        $resizedPath = Mage::getBaseDir( 'media' ) . DS . "catalog" . DS . "product" . DS . "cache" .DS. "category" .DS. "resized" . DS . $img;


        if (file_exists($resizedPath)) {
            $baseUrl = str_replace("$storeCode/", '', $this->getBaseUrl());
            return $baseUrl . 'media' . DS . 'catalog' . DS . 'product' . DS . 'cache' . DS . 'category' . DS . 'resized' . DS . $img;
        }

        return $this->_resizeImage($img);

    }
}
