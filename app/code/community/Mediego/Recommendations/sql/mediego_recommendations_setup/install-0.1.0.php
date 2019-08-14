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

/* @var @installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('mediego_recommendations/default_recommendations'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
                array(
                    'nullable' => false,
                    'identity' => true,
                    'unsigned' => true,
                    'primary' => true,
                ),
                'Primary key, auto increment')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Product Id')
    ->addColumn('position', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
                array(
                    'unsigned'  => true,
                    'nullable'  => false,
                    'default'   => 0,
                ),
                'Position')
    ->addForeignKey(
        $installer->getFkName(
            'mediego_recommendations/default_recommendations',
            'product_id',
            'catalog/product',
            'entity_id'
        ),
        'product_id',
        $installer->getTable('catalog/product'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Default recommendations for module Mediego_Recommendations');
$installer->getConnection()->createTable($table);

$installer->endSetup();
