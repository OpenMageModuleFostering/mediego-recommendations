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
class Mediego_Recommendations_Model_Resource_Default_Recommendations extends Mage_Core_Model_Resource_Db_Abstract
{
	public function _construct()
	{
		$this->_init("mediego_recommendations/default_recommendations", "id");
	}
}