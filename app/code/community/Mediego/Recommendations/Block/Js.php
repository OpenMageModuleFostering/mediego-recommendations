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
class Mediego_Recommendations_Block_Js extends Mage_Core_Block_Template
{
    public function _construct()
    {
        $this->setTemplate('mediegorecommendations/js.phtml');

        parent::_construct();

        $this->addData(array('cache_lifetime' => false));
        $this->addCacheTag(array(
            Mage_Core_Model_Store::CACHE_TAG,
            Mage_Cms_Model_Block::CACHE_TAG,
        ));
    }

    public function getCacheKeyInfo()
    {
        return array(
            'MEDIEGO_JS',
            Mage::app()->getStore()->getCode()
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