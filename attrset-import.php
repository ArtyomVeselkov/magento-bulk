#!/usr/bin/php
<?php
require_once 'lib/init.php';
require_once 'lib/attributeset_functions.php';

if (count($argv) < 2) {
	echo "Import attribute sets\n";
	echo "Usage: attrset-import.php INPUT_XML\n";
	exit(1);
}

echo "Loading attribute sets...";
$entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
$collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
	->setEntityTypeFilter($entityType->getId());
$sets = array();
foreach ($collection as $attributeSet) {
	$sets[$attributeSet->getAttributeSetName()] = $attributeSet->getId();
}
echo ' '. count($sets) . " found.\n";

echo "Loading user defined attributes...";
$udAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
$udAttrs->addFieldToFilter('main_table.is_user_defined', 1);
$udAttrLookup = array();
$udAttrCodes = array();
foreach ($udAttrs as $attr) {
	$udAttrCodes[] = $attr->getAttributeCode();
	$udAttrLookup[$attr->getAttributeCode()] = $attr;
}
echo ' '. join(' ', $udAttrCodes) ."\n";

// Load File XML
$xmlFilename = $argv[1];
echo "Loading $xmlFilename...";
$attrsetsXml = simplexml_load_file($xmlFilename);
echo " Loaded.\n";
foreach ($attrsetsXml as $setEl) {
	$name = (string) $setEl->name;
	$base = (string) $setEl->base;
	$attributesStr = (string) $setEl->attributesStr;
	
	// check if set with requested $skeletonSetId exists
	if (!isset($sets[$base])) {
		throw new Exception("Cannot find base attribute set '$base'");
	}
	$baseId = $sets[$base];
	
	$attributeIds = array();
	$attrCodes = isset($attributesStr) ? explode(',', $attributesStr) : array();
	echo "Lookup attributes...";
	foreach ($attrCodes as $attrCode) {
		echo " $attrCode";
		if (!isset($udAttrLookup[$attrCode])) {
			echo "Attribute $attrCode not found, skipping!\n";
			continue;
		}
		$attributeId = $udAttrLookup[$attrCode]->getId();
		echo "=$attributeId";
		$attributeIds[] = $attributeId;
	}
	echo " Lookup complete.\n";
	
	$attributeSetId = createAttributeSet($baseId, $name, $attributeIds);
	
	$sets[$name] = $attributeSetId;
}
