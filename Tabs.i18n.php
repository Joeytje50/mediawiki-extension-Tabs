<?php
/**
 * Internationalisation file for Tabs extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Pim (Joeytje50)
 */
$messages['en'] = array(
	'tabs-desc' => 'Adds <code>&lt;tabs&gt;</code>, <code>&lt;tab&gt;</code> and <code>&lt;tabdef&gt;</code> tags for creating tabbed layout.',
	'tabs-tab-label' => 'Tab $1',
	'tabs-toggle-open' => 'Show contents',
	'tabs-toggle-close' => 'Hide contents',
	'tabs-dropdown-label' => 'Show dropdown',
	'tabs-dropdown-bgcolor' => 'white', # do not translate or duplicate this message to other languages
);
$magicWords['en'] = array(
	'tab' => array(0, 'tab'),
);

/** Message documentation
 * @author Pim (Joeytje50)
 */
$messages['qqq'] = array(
	'tabs-desc' => '{{desc|name=Tabs|url=http://www.mediawiki.org/wiki/Extension:Tabs}}',
	'tabs-tab-label' => 'The default label for a tabs menu. Parameter $1 stands for the index of the tab.',
	'tabs-toggle-open' => 'The default opening label for toggle boxes.',
	'tabs-toggle-close' => 'The default closing label for toggle boxes.',
	'tabs-dropdown-label' => 'The default label for a dropdown menu.',
	'tabs-dropdown-bgcolor' => '{{notranslate}} The default background-color for dropdown menus.',
);

/** Dutch (Nederlands)
 * @author Pim (Joeytje50)
 */
$messages['nl'] = array(
	'tabs-desc' => 'Voegt <code>&lt;tabs&gt;</code>, <code>&lt;tab&gt;</code> and <code>&lt;tabdef&gt;</code> toe voor opmaak met tabbladen.',
	'tabs-tab-label-placeholder' => 'Tab $1',
	'tabs-toggle-open-placeholder' => 'Toon inhoud',
	'tabs-toggle-close-placeholder' => 'Verberg inhoud',
	'tabs-dropdown-placeholder' => 'Toon uitklapmenu',
);