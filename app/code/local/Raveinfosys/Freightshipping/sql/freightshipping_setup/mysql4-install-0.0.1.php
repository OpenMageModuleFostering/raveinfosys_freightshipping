<?php

$installer = $this;

$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

/**
 * Adding Attribute Group
 */
$setup->addAttributeGroup('catalog_product', 'Default', 'Freight Shipping', 1000);

/**
 * Adding Different Attributes
 */
$attributes = array(
				array('label' => 'Height','input' => 'text','type' => 'text', 'code'=>'freight_height'),
				array('label' => 'Width','input' => 'text','type' => 'text', 'code'=>'freight_width'),
				array('label' => 'Length','input' => 'text','type' => 'text', 'code'=>'freight_length'),
				array('label' => 'Freight/Ship Class','input' => 'text','type' => 'text','code'=>'freight_class')
			);

/**
 * The attribute added will be displayed under the group/tab Freight Shipping in product edit page 
 */
foreach($attributes as $_attribute){
	if(null === Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product',$_attribute['code'])->getId())
	{
		$_attributeData = array(
			'group'             => 'Freight Shipping',
			'label'             => $_attribute['label'],
			'type'              => $_attribute['type'],
			'input'             => $_attribute['input'],
			'required'          => false,
			'frontend_class' 	=> 'validate-number',
			'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE			
		);	
		$setup->addAttribute('catalog_product', $_attribute['code'], $_attributeData);
	}
}
$installer->endSetup();