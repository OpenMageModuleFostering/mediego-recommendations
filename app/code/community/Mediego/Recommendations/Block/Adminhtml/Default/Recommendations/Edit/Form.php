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
class Mediego_Recommendations_Block_Adminhtml_Default_Recommendations_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        // creates the form
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
     	    'enctype' => 'multipart/form-data'
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        // gets the model from the registry and prefix the product_id with product/ for the chooser to work
        $model = Mage::registry('mediego_recommendations_data');
        $productId = $model->getData('product_id');

        if ($productId) {
            $model->setProductId('product/'.$productId);
        }

        // add the field set
        $fieldset = $form->addFieldset('mediego_recommendations_form', array(
            'legend' => $this->__('Details')
        ));

        // adds the product chooser field
        $productConfig = array(
            'input_name'  => 'product_id',
            'input_label' => $this->__('Product'),
            'button_text' => $this->__('Select Product...'),
            'required'    => true
        );

        $chooserHelper = Mage::helper('mediego_recommendations/chooser');
        $chooserHelper->createProductChooser($model, $fieldset, $productConfig);

        // adds the position field
        $fieldset->addField('position', 'text', array(
            'name'  => 'position',
            'label' => $this->__('Position')
        ));

        // updates the form values from the model
        $form->setValues($model->getData());

        return parent::_prepareForm();
    }
}