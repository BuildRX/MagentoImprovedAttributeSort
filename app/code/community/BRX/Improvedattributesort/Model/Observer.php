<?php
/**
 * BuildRX
 *
 * NOTICE OF LICENSE
 * 
 * This code is part of BuildRX's internal libraries.
 * Unless licensed otherwise you may only use this extension in Magento stores worked on by BuildRX.
 *
 * Improvedattributesort
 * 
 * @category    BRX
 * @package     BRX_Stockfilter
 * @copyright   Copyright (c) 2013 BuildRX (http://www.buildrx.com)
 */

/**
 * Improvedattributesort observer
 *
 * @category    BRX
 * @package     BRX_Improvedattributesort
 * @author      Matt Dunbar <matt@buildrx.com>
 */
class BRX_Improvedattributesort_Model_Observer {
    /**
     * Sort products by the attribute's sort order rather than its name alphabetically.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogProductCollectionLoadBefore(Varien_Event_Observer $observer) {
        $collection = $observer->getCollection();
        $order = Mage::getBlockSingleton('catalog/product_list_toolbar')->getCurrentOrder();
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $order);

        //Magento won't let us rewrite Eav_Model_Entity_Attribute_Source_Table because of the way its loaded
        //To circumvent that we need to re-join the attributes then sort by them.
        //This should really be happening in the core and will likely make it into a future version of Magento.

        $valueTable1    = $attribute->getAttributeCode() . '_a1';
        $valueTable2    = $attribute->getAttributeCode() . '_a2';
        $collection->getSelect()
            ->joinLeft(
                array($valueTable1 => $attribute->getBackend()->getTable()),
                "e.entity_id={$valueTable1}.entity_id"
                . " AND {$valueTable1}.attribute_id='{$attribute->getId()}'"
                . " AND {$valueTable1}.store_id=0",
                array())
            ->joinLeft(
                array($valueTable2 => $attribute->getBackend()->getTable()),
                "e.entity_id={$valueTable2}.entity_id"
                . " AND {$valueTable2}.attribute_id='{$attribute->getId()}'"
                . " AND {$valueTable2}.store_id='{$collection->getStoreId()}'",
                array()
            );

        $dir = strtoupper(Mage::getBlockSingleton('catalog/product_list_toolbar')->getCurrentDirection());
        $collection->getSelect()->reset(Zend_Db_Select::ORDER);
        $collection->getSelect()
            ->joinLeft(Mage::getSingleton('core/resource')->getTableName('eav_attribute_option') .' AS eao', "eao.option_id=IF({$order}_a2.value_id > 0, {$order}_a2.value, {$order}_a1.value)", array("sort_order" => 'eao.option_id'))
            ->order(new Zend_Db_Expr('eao.sort_order '.$dir));
    }
}
?>