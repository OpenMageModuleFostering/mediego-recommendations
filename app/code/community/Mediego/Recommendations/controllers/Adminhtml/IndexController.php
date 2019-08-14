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
class Mediego_Recommendations_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/mediego_recommendations');
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('catalog/mediego_recommendations');
        $this->renderLayout();
    }

    public function editAction()
    {
        $recommendationId = $this->getRequest()->getParam('id');
        $recommendation = Mage::getModel('mediego_recommendations/default_recommendations');

        $recommendation->load($recommendationId);

        Mage::register('mediego_recommendations_data', $recommendation);

        $this->loadLayout();
        $this->_setActiveMenu('catalog/mediego_recommendations');
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_addContent($this->getLayout()->createBlock('mediego_recommendations/adminhtml_default_recommendations_edit'));

        $this->renderLayout();
    }

    public function newAction()
    {
        $recommendation = Mage::getModel('mediego_recommendations/default_recommendations');

        Mage::register('mediego_recommendations_data', $recommendation);

        $this->loadLayout();
        $this->_setActiveMenu('catalog/mediego_recommendations');
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_addContent($this->getLayout()->createBlock('mediego_recommendations/adminhtml_default_recommendations_edit'));

        $this->renderLayout();
    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            try {
                $postData = $this->getRequest()->getPost();
                $productId = (int) explode('/',$postData['product_id'])[1];
                $position = $postData['position'];

                $recommendation = Mage::getModel('mediego_recommendations/default_recommendations');
                $recommendation->setId($this->getRequest()->getParam('id'))
                               ->setProductId($productId)
                               ->setPosition($position)
                               ->save();

                Mage::app()->cleanCache(array(Mediego_Recommendations_Model_Default_Recommendations::CACHE_TAG));

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Successfully saved'));
                Mage::getSingleton('adminhtml/session')->setDefaultRecommendationData(false);

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setDefaultRecommendationData($this->getRequest()->getPost());

                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }

        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try
            {
                $recommendation = Mage::getModel('mediego_recommendations/default_recommendations');
                $recommendation->setId($this->getRequest()->getParam('id'))
                               ->delete();

                Mage::app()->cleanCache(array(Mediego_Recommendations_Model_Default_Recommendations::CACHE_TAG));

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Successfully deleted'));

                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }

        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('ids');

        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select recommendations'));
        } else {
            try {
                $recommendations = Mage::getModel('mediego_recommendations/default_recommendations')
                    ->getCollection()
                    ->addFieldToFilter('id', array('in' => array($ids)));

                foreach ($recommendations as $recommendation) {
                    $recommendation->delete();
                }

                Mage::app()->cleanCache(array(Mediego_Recommendations_Model_Default_Recommendations::CACHE_TAG));

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('%d recommendation(s) were deleted', count($ids)));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }
}
