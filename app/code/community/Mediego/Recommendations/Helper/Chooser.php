<?php
/**
 * Mediego
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mediego
 * @package    Mediego_Recommendations
 * @author     Orinoko <contact@orinoko.fr>
 * @copyright  Copyright (c) 2014-2015 Mediego (http://mediego.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Mediego recommendations
 *
 * Credits goes to Tsvetan Stoychev <tsvetan.stoychev@jarlssen.de>  @  http://www.jarlssen.de
 *
 * @category   Mediego
 * @package    Mediego_Recommendations
 * @author     Orinoko <contact@orinoko.fr>
 */
class Mediego_Recommendations_Helper_Chooser extends Mage_Core_Helper_Abstract
{
    public function createProductChooser($dataModel, $fieldset, $config)
    {
        $blockAlias = 'adminhtml/catalog_product_widget_chooser';

        $this->_prepareChooser($dataModel, $fieldset, $config, $blockAlias);

        return $this;
    }

    protected function _prepareChooser($dataModel, $fieldset, $config, $blockAlias)
    {
        $this->_populateMissingConfigValues($config, $blockAlias);

        $chooserConfigData = $this->_prepareChooserConfig($config, $blockAlias);
        $chooserBlock = Mage::app()->getLayout()->createBlock($blockAlias, '', $chooserConfigData);

        $element = $this->_createFormElement($dataModel, $fieldset, $config);
        $chooserBlock->setConfig($chooserConfigData)
                     ->setFieldsetId($fieldset->getId())
                     ->prepareElementHtml($element);

        $this->_fixChooserAjaxUrl($element);

        return $this;
    }

    protected function _populateMissingConfigValues(&$config, $blockAlias)
    {
        $currentWidgetKey = str_replace('adminhtml/', '',$blockAlias);
        $chooserDefaults = Mage::getStoreConfig($this->__('Select Product...'));

        if (!isset($chooserDefaults[$currentWidgetKey])) {
            $currentWidgetKey = 'default';
        }

        foreach ($chooserDefaults[$currentWidgetKey] as $configKey => $value) {
            if (!isset($config[$configKey])) {
                $config[$configKey] = $value;
            }
        }

        return $this;
    }

    protected function _createFormElement($dataModel, $fieldset, $config)
    {
        $isRequired = (isset($config['required']) && true === $config['required']) ? true : false;

        $inputConfig = array(
            'name'  => $config['input_name'],
            'label' => $config['input_label'],
            'required' => $isRequired
        );

        if (!isset($config['input_id'])) {
            $config['input_id'] = $config['input_name'];
        }

        $element = $fieldset->addField($config['input_id'], 'label', $inputConfig);
        $element->setValue($dataModel->getData($element->getId()));
        $dataModel->setData($element->getId(),'');

        return $element;
    }

    protected function _prepareChooserConfig($config, $blockAlias)
    {
        return array(
            'button' => array('open' => $config['button_text'],
                              'type' => $blockAlias)
        );
    }

    protected function _fixChooserAjaxUrl($element)
    {
        $adminPath = (string) Mage::getConfig()->getNode(Mage_Adminhtml_Helper_Data::XML_PATH_ADMINHTML_ROUTER_FRONTNAME);
        $currentRouterName = Mage::app()->getRequest()->getModuleName();

        if ($adminPath != $currentRouterName) {
            $afterElementHtml = $element->getAfterElementHtml();
            $afterElementHtml = str_replace('/' . $currentRouterName . '/','/' . $adminPath . '/', $afterElementHtml);
            $element->setAfterElementHtml($afterElementHtml);
        }
    }
}