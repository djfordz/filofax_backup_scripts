<?php

class Filofax_BulkUpload_Adminhtml_BulkImport_BulkImageImportController extends Mage_Adminhtml_Controller_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('filofax_bulkupload/image/import');
    }

    protected function _initAction()
    {
        $this
            ->loadLayout();

        $block = $this->getLayout()
            ->createBlock('core/text', 'filofax_bulk_image_import')
            ->setText('<h1>Filofax Bulk Image Import</h1>');

        $text = $this->getLayout()
            ->createBlock('core/text', 'filofax_bulk_image_import_directions')
            ->setText(
                '<h3>This module allows for the bulk import of images.<br />
                A specific image naming convention must be adhered to.</h3>
                <p>The convention allows for the module to search for the product by sku and add the image to the specified product.</p>
                 <p>All images must be uploaded to <pre>[mageroot]/media/import/</pre> directory prior to running the import process.</p>
                <p>All image must start with product sku to be attached to and select either 1 or 0 with an undersore between each value for <pre>base_image, small_image, thumbnail</pre></p>
                <p>An example of an image file name is below: <br/>
                <pre>20-68208_1_1_1.jpg</pre>
                or
                <pre>19-65400_0_0_0.jpg</pre>
                or<br />
                the image can have just the sku as a name like
                <pre>186534.jpg</pre>
                this configuration assumes there are no options for base_image, small_image, and thumbnail.</p>
                <p>Other configurations are also compatible as long as the sku is the first part of the name followed by _<br />
                and as long as no options are needed. So
                <pre>18-206820_large.jpg</pre>
                would be valid as well.</p>
                <p>If a label needs to be added, the underscores are mandatory and the label will be the last part of the name.<br />example:
                <pre>19-18745_0_0_0_Purple.jpg</pre></p>
                <p>After all images are uploaded via ftp to [mageroot]/media/import/</p>
                <p>To Run, select the button below and the importer will run, parsing all images.</p>'
            );

        
        $button = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'        => 'filofax_image_import_button',
                'label'     => Mage::helper('filofax_bulkupload')->__('Run Image Import'),
                'onclick'   => 'javascript:execute(); return false;'
            ));

        $url = $this->getUrl('filofax_bulkupload/adminhtml_bulkImport_bulkImageImport/execute');
        $js = $this->getLayout()
            ->createBlock('core/text')
            ->setText('
                <script>
                        function execute(callback) {
                            var mask = document.getElementById("loading-mask");        
                            mask.style.display = "block";
                            var url = "' . $url . '";
                            var xhr = new XMLHttpRequest();
                            if (!xhr) {
                                alert("httpRequest failed");
                                console.log("httpRequest failed");
                            }
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4) {
                                    if (xhr.response) {
                                        mask.style.display = "none";
                                    }
                                }
                            }
                            xhr.open("GET", url);
                            xhr.send("execute=true");
                        }
                </script>'
            );

        $this->_addContent($block);
        $this->_addContent($text);
        $this->_addContent($button);
        $this->_addJs($js);

        return $this;
    }

    public function indexAction()
    {
        $this
            ->_initAction()
            ->renderLayout()
        ;
    }
    public function executeAction()
    {
        Mage::getModel('filofax_bulkupload/bulkImageUpload')
            ->execute();
    }
}

