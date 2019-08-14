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
class Mediego_Recommendations_Block_Adminhtml_Default_Recommendations_Grid_Container extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_default_recommendations';
        $this->_blockGroup = 'mediego_recommendations';
        $this->_addButtonLabel = $this->__('Add Recommendation');

        parent::__construct();

        $recommendations = Mage::getModel('mediego_recommendations/default_recommendations')->getCollection();

        $hint = $this->__("The default recommendations acts as a fallback when Mediego can not recommend products. For the widgets to be displayed:") . "<br>∙&nbsp;"
              . $this->__("you need enough default recommendations to fill them") . "<br>∙&nbsp;"
              . $this->__("the products must be visible and enabled") . "<br>"
              . $this->__("Warning: Duplicate products will not be displayed");

        Mage::getSingleton('adminhtml/session')->addNotice($hint);
    }

    public function getHeaderText()
    {
        return $this->__('Default Recommendations');
    }

    public function getHeaderCssClass()
    {
        return 'icon-head head-mediego';
    }
}