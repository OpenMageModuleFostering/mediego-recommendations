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
class Mediego_Recommendations_Block_Adminhtml_Default_Recommendations_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('mediegoRecommendationsGrid');
        $this->setDefaultSort('position');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        // gets all the recommendations (we do not even apply paging at this stage since there will never be many rows)
        $recommendations = Mage::getModel('mediego_recommendations/default_recommendations')
            ->getCollection()
            ->addFieldToSelect(array('id','position','product_id'));

        $productIds = $recommendations->getColumnValues('product_id');

        // gets the products
        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect(array('entity_id','name','sku','visibility','status'))
            ->addFieldToFilter('entity_id', array('in' => $productIds));

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $products->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        }

        // groups the products by their id
        $productById = array();

        foreach ($products as $p) {
            $productById[$p->getEntityId()] = $p;
        }

        // builds the rows
        $items = array();

        foreach ($recommendations as $recommendation) {
            $item = new Varien_Object();
            $item->setId($recommendation->getId());
            $item->setPosition($recommendation->getPosition());

            if (isset($productById[$recommendation->getProductId()])) {
                $product = $productById[$recommendation->getProductId()];
                $item->setName($product->getName());
                $item->setSku($product->getSku());
                $item->setQty($product->getQty());
                $item->setVisibility($product->getVisibility());
                $item->setStatus($product->getStatus());
            }

            $items[] = $item;
        }

        $collection = new Mediego_Recommendations_Model_Collection_Memory($items, function($i) { return $i->getData(); });
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header' => 'ID',
            'align' => 'right',
            'width' => '50px',
            'index' => 'id'
        ));

        $this->addColumn('position', array(
            'header' => $this->__('Position'),
            'align' => 'left',
            'type' => 'number',
            'index' => 'position',
            'width' => '70px'
        ));

        $this->addColumn('name', array(
            'header' => $this->__('Product'),
            'align' => 'left',
            'index' => 'name'
        ));

        $this->addColumn('sku', array(
            'header' => $this->__('SKU'),
            'width'  => '80',
            'index'  => 'sku',
        ));

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $this->addColumn('qty', array(
                'header'=> Mage::helper('catalog')->__('Qty'),
                'width' => '100px',
                'type'  => 'number',
                'index' => 'qty',
            ));
        }

        $this->addColumn('visibility', array(
            'header'=> Mage::helper('catalog')->__('Visibility'),
            'width' => '180px',
            'index' => 'visibility',
            'type'  => 'options',
            'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
        ));

        $this->addColumn('status', array(
            'header'=> Mage::helper('catalog')->__('Status'),
            'width' => '70px',
            'index' => 'status',
            'type'  => 'options',
            'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> $this->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete', array('' => '')),        // public function massDeleteAction() in Mage_Adminhtml_Tax_RateController
            'confirm' => $this->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'id' => $row->getId()
        ));
    }
}