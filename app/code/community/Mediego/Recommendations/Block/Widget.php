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
 * @category   Mediego
 * @package    Mediego_Recommendations
 * @author     Orinoko <contact@orinoko.fr>
 */
class Mediego_Recommendations_Block_Widget extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    public function _construct()
    {
        $this->setTemplate('mediegorecommendations/recommendations.phtml');

        parent::_construct();

        $this->addData(array('cache_lifetime' => false));
        $this->addCacheTag(array(
            Mage_Core_Model_Store::CACHE_TAG,
            Mage_Cms_Model_Block::CACHE_TAG,
            Mage_Catalog_Model_Product::CACHE_TAG,
            Mediego_Recommendations_Model_Default_Recommendations::CACHE_TAG
        ));
    }

    public function getLayoutOrientation()
    {
        $layout = $this->getData('layout'); // (getLayout is reserved)

        if (preg_match('/^[0-9]+x$/', $layout)) {
            return "horizontal";
        } else if (preg_match('/^x[0-9]+$/', $layout)) {
            return "vertical";
        } else {
            return null;
        }
    }

    public function getDisplayPrice()
    {
        return $this->getData('display_price');
    }

    public function getGridSize()
    {
        $layoutProductCount = $this->getLayoutProductCount();

        if ($layoutProductCount > 4) {
            return $layoutProductCount/2;
        } else {
            return $layoutProductCount;
        }
    }

    public function getLayoutProductCount()
    {
        $layout = $this->getData('layout'); // (getLayout is reserved)
        $matches = array();

        if (preg_match('/^([0-9]+)x$/', $layout, $matches)) {
            return (int) $matches[1];
        } else if (preg_match('/^x([0-9]+)$/', $layout, $matches)) {
            return (int) $matches[1];
        } else {
            return 0;
        }
    }

	public function getProducts()
	{
        // gets default recommendations product ids (in the display order). Note we load all of them and use paging
        // later with the producs collection only to have a better chance of having enough products
        $productIds = Mage::getModel('mediego_recommendations/default_recommendations')
            ->getCollection()
            ->addFieldToSelect(array('id','position','product_id'))
            ->setOrder('position','asc')
            ->setOrder('id')
            ->getColumnValues('product_id');

        // gets the products
        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $productIds))
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addUrlRewrite();

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);

        $products->setPageSize($this->getLayoutProductCount());

        // groups the products by their id
        $productById = array();

        foreach ($products as $p) {
            $productById[$p->getEntityId()] = $p;
        }

        // builds the items
        $items = new Varien_Data_Collection();

        foreach ($productIds as $productId) {
            if (isset($productById[$productId])) {
                $items->addItem($productById[$productId]);
            }
        }

        return $items;
	}

	public function getCacheKeyInfo()
	{
	    return array(
            'MEDIEGO_RECOMMENDATIONS_WIDGET',
            Mage::app()->getStore()->getCode(),
            $this->getTemplateFile(),
            'template' => $this->getTemplate(),
	        $this->getModuleKey() ?: '',
	        $this->getData('layout'),
	        $this->getData('display_price'),
	        $this->getData('custom_title')
	    );
	}

	protected function _toHtml()
	{
        if (!Mage::helper('mediego_recommendations')->isOutputEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }
}