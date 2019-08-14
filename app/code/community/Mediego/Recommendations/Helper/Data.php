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
class Mediego_Recommendations_Helper_Data extends Mage_Core_Helper_Data
{
    private $_enabled = null;
    private $_opengraphEnabled = null;
    private $_outputEnabled = null;

    public function isEnabled()
    {
        if (is_null($this->_enabled)) {
            $this->_enabled = Mage::getStoreConfigFlag('mediego_recommendations/general/enabled');
        }

        return $this->_enabled;
    }

    public function isOpengraphEnabled()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (is_null($this->_opengraphEnabled)) {
            $this->_opengraphEnabled = Mage::getStoreConfigFlag('mediego_recommendations/general/open_graph');
        }

        return $this->_opengraphEnabled;
    }

    public function isOutputEnabled()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (is_null($this->_outputEnabled)) {
            $this->_outputEnabled = Mage::helper('core')->isModuleOutputEnabled('Mediego_Recommendations');
        }

        return $this->_outputEnabled;
    }
}