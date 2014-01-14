<?php
/**
 * This tag extension creates the <tabs> and <tab> tags for creating tab interfaces and toggleboxes on wiki pages.
 * 
 * @example Tabs/Tabs.examples.txt
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 */

/*Possible features to add:
 * Dropdown menus with :hover and button:focus
 * Possibility of showing <tab> on multiple indices, for things like <t i="1,2">foo <t i="1">bar</t><t i="2">baz</t></t><t i="3">quux</t>
 *		do this by just adding both .tab-content-1 and .tab-content-2 to the tab
 * Use of a parser-function alternative for the <tab> tag: {{#tab:a|b|c|d}}, would be useful for inline things
 */

class Tabs {	
	/**
	 * Initiate the tags
	 * @param Parser &$parser
	 * @return boolean true
	 */
	public static function init( &$parser ) {
		$parser->tabsData = array(
			'tabsCount' => 0, // Counts the index of the <tabs> tag on the page. Increments by 1 before parsing the tag.
			'tabCount' => 0, // Same, but for <tab> instead.
			'addedStatics' => false, // checks if static styles have been added, so that it isn't done multiple times.
			'toStyle' => 0, // Counts the maximum amount of <tab> tags used within a single <tabs> tag. Used to determine the amount of lines to be added to the dynamic stylesheet.
			'nested' => false, // Keeps track of whether the <tab> is nested within a <tabs> or not.
			'tabNames' => array(), // Contains a list of the previously used tab names in that scope. 
			'labels' => array(), // Lists the labels that need to be made within <tabs>. Example: array(1 => 'Tab 1', 2 => 'some tab label');
			'dropdown' => false // Used in combination with 'nested'; keeps track of whether the <tab> is nested inside a dropdown.
		);
		$parser->setHook( 'tab', array( new self(), 'renderTab' ) );
		$parser->setHook( 'tabs', array( new self(), 'renderTabs' ) );
		return true;
	}
	
	/**
	 * Converts each <tab> into either a togglebox, or the contents of one tab within a <tabs> tag.
	 *
	 * @param string $input
	 * @param array $attr
	 * @param Parser $parser
	 * @return string
	 */
	public function renderTab($input, $attr = array(), $parser) {
		$form = $parser->tabsData['tabCount'] === 0 ? $this->insertCSSJS($parser) : ''; // init styles, set the return <form> tag as $form.
		++$parser->tabsData['tabCount'];
		$names = &$parser->tabsData['tabNames'];
		$nested = $parser->tabsData['nested'];
		// Default value for the tab's given index: index attribute's value, or else the index of the tab with the same name as name attribute, or else the tab index
		if (!$nested) {
			$index = 0; // indices do nothing for non-nested tabs, so don't even bother doing the computations.
		} elseif (isset($attr['index']) && intval($attr['index']) <= count($names)) {
			$exploded = explode(',', $attr['index']);
			if (count($exploded) === 1) { // if the index parameter is simply a single index
				$index = intval($attr['index']); // if the index is given, and it isn't greater than the current index + 1.
			} else { // if the index parameter contains a comma seperated list of indices.
				$index = array();
				foreach ($exploded as $i) {
					if (isset($names[intval($i)])) { // Only if the index already exists, multiple input selection is allowed.
						$index[] = intval($i);
					}
				}
				if (count($index) === 0) $index = intval($attr['index']); // Change to the first index given, if none of the entered indices already exist.
			}
		} elseif (isset($attr['name']) && array_search($attr['name'], $names) !== false)
			$index = array_search($attr['name'], $names) ; // if index is not defined, but the name is, use the index of the tabname.
		else {
			$index = count($names)+1; // index of this tab in this scope. Plus one because tabs are 1-based, arrays are 0-based.
		}
		
		$classPrefix = '';
		if ($nested && gettype($index) === 'integer') // Note: This is defined seperately for toggleboxes, because of the different classes required.
			$classPrefix .= "tabs-content tabs-content-$index";
		elseif ($nested) {
			$classPrefix .= 'tabs-content';
			foreach ($index as $i) {
				$classPrefix .= " tabs-content-$i"; // Having multiple indices associated with the content makes it show for multiple tabs.
			}
		}
		
		if (!isset($attr['class']))
			$attr['class'] = $classPrefix; // only the prefix if no classes have been defined
		else
			$attr['class'] = trim("$classPrefix ".htmlspecialchars($attr['class']));
		
		//TODO: Also needs to be able to take it when fewer indices than names are defined, or even no index is defined.
		if (gettype($index) === 'array') {
			$name = array();
			$n = 0;
			foreach ($index as $i) { // this loop basically does the same as what follows in its "parent" if-else block, but for each item in $index.
				$explname = isset($attr['name']) && trim($attr['name']) ? explode(',', $attr['name']) : array();
				if (isset($names[$i-1]))
					$name[] = $names[$i-1];
				else // Note: only increment $n if the name is not already defined. Only unnamed indices will get a name attached to them.
					$name[] = isset($explname[$n]) && trim($explname[$n]) ? trim($explname[$n++]) : wgMessage('tabs-tab-label-placeholder', $i);
			}
		} elseif (isset($names[$index-1])) // if array $names already has a name defined at position $index, use that.
			$name = $names[$index-1]; // minus 1 because tabs are 1-based, arrays 0-based.
		else // otherwise, use the entered name, or the $index with a "Tab " prefix if it is not defined or empty.
			$name = trim(isset($attr['name']) && trim($attr['name']) ? $attr['name'] : wfMessage('tabs-tab-label-placeholder', $index));

		if (!$nested) { // This runs when the tab is not nested inside a <tabs> tag.
			$nameAttrs = array(
				'name'=>isset($attr['name']),
				'openname'=>isset($attr['openname']),
				'closename'=>isset($attr['closename']),
			);
			$checked = isset($attr['collapsed']) ? '' : ' checked="checked"';
			$id = 'Tabs_'.$parser->tabsData['tabCount'];
			
			/*
			 * If only one of the openname and closename attributes is defined, the both will take the defined one's value
			 * If neither is defined, but the name attribute is, both will take the name attribute's value
			 * If all three are undefined, the default "Show/Hide content" will be used
			 */
			if ($nameAttrs['openname'] && $nameAttrs['closename']) {
				$openname = htmlspecialchars($attr['openname']);
				$closename = htmlspecialchars($attr['closename']);
			} elseif ($nameAttrs['openname'] && !$nameAttrs['closename']) $openname = $closename = htmlspecialchars($attr['openname']);
			elseif ($nameAttrs['closename'] && !$nameAttrs['openname']) $openname = $closename = htmlspecialchars($attr['closename']);
			elseif (!$nameAttrs['openname'] && !$nameAttrs['closename'] && $nameAttrs['name']) $openname = $closename = htmlspecialchars($attr['name']);
			elseif (!$nameAttrs['openname'] && !$nameAttrs['closename']) {
				$openname = wfMessage('tabs-toggle-open-placeholder');
				$closename = wfMessage('tabs-toggle-close-placeholder');
			}
			
			// Check if the togglebox should be displayed inline. No need to check for the `block` attribute, since the default is display:block;
			$inline = isset($attr['inline']) ? ' tabs-inline' : '';
			$label = "<input class=\"tabs-input\" form=\"tabs-inputform\" type=\"checkbox\" id=\"$id\"$checked/><label class=\"tabs-label\" for=\"$id\"><span class=\"tabs-open\">$openname</span><span class=\"tabs-close\">$closename</span></label>";
			$attr['class'] = "tabs tabs-togglebox$inline ".$attr['class'];
			$attrStr = $this->getSafeAttrs($attr);
			$container = array(
				"<div$attrStr><div class=\"tabs-container\">$label",
				'</div></div>'
			);
			$containerStyle = '';
			if (isset($attr['container'])) $containerStyle = htmlspecialchars($attr['container']);
			$attrStr = " class=\"tabs-content\" style=\"$containerStyle\""; //the attrStr is used in the outer div, so only the containerStyle should be applied to the content div.
		} else { // this runs when the tab is nested inside a <tabs> tag.
			$container = array('', '');
			if (gettype($name) === 'array') {
				foreach ($name as $n) {
					if (array_search($n, $names) === fase)
						$names[] = $name;
				}
			} elseif (array_search($name, $names) === false) // append name if it's not already in the list.
				$names[] = $name;
			
			if (isset($attr['inline']))
				$ib = 'tabs-inline';
			else if (isset($attr['block']))
				$ib = 'tabs-block';
			else
				$ib = '';
			$attr['class'] = "$ib ".$attr['class'];
			$attrStr = $this->getSafeAttrs($attr);
			$parser->tabsData['labels'][intval($index)] = $name; // Store the index and the name so this can be used within the <tabs> hook to create labels
		}
		if ($input === null) return ''; // return empty string if the tag is self-closing. This can be used to pre-define tabs for referring to via the index later.

		$parser->tabsData['nested'] = false; // temporary
		$newstr = $parser->recursiveTagParse($input);
		$parser->tabsData['nested'] = $nested; // revert
		return $form.$container[0]."<div$attrStr>$newstr</div>".$container[1];
	}
	
	/**
	 * Converts each <tabs> to a tab layout.
	 *
	 * @param string $input
	 * @param array $attr
	 * @param Parser $parser
	 * @return string
	 */
	public function renderTabs($input, $attr = array(), $parser) {
		if (!isset($input)) return ''; // Exit if the tag is self-closing. <tabs> is a container element, so should always have something in it.
		$form = $parser->tabsData['tabCount'] === 0 ? $this->insertCSSJS($parser) : ''; // init styles, set the return <form> tag as $form.
		if ($parser->tabsData['tabsCount'] === 0) $this->insertCSSJS($parser); // init styles
		$count = ++$parser->tabsData['tabsCount'];
		$attr['class'] = isset($attr['class']) ? 'tabs tabs-tabbox '.$attr['class'] : 'tabs tabs-tabbox';
		$attrStr = $this->getSafeAttrs($attr);
		$containerStyle = '';
		if (isset($attr['container'])) $containerStyle = htmlspecialchars($attr['container']);
		
		// CLEARING:
		$tabnames = $parser->tabsData['tabNames']; // Copy this array's value, to reset it to this value after parsing the inner <tab>s.
		$parser->tabsData['tabNames'] = array(); // temporarily clear this array, so that only the <tab>s within this <tabs> tag are tracked.
		$parser->tabsData['labels'] = array(); // Reset after previous usage
		$parser->tabsData['nested'] = true;
		// PARSING
		$newstr = $parser->recursiveTagParse($input);
		// AND RESETTING (to their original values):
		$parser->tabsData['tabNames'] = $tabnames; // reset to the value it had before parsing the nested <tab>s. All nested <tab>s are "forgotten".
		$parser->tabsData['nested'] = false; // reset
		
		/**
		 * The default value for $labels creates a seperate input for the default tab, which has no label attached to it.
		 * This is to allow any scripts to be able to check easily if the user has changed the shown tab at all,
		 * by checking if this 0th input is checked.
		 */
		$labels = "<input type=\"radio\" form=\"tabs-inputform\" id=\"tabs-input-$count-0\" name=\"tabs-$count\" class=\"tabs-input tabs-input-0\" checked/>";
		$indices = array(); // this is to most accurately count the amount of <tab>s in this <tabs> tag.
		foreach ($parser->tabsData['labels'] as $i => $n) {
			$indices[] = $i;
			$labels .= $this->makeLabel($i, $n, $count);
		}
		
		$toStyle = &$parser->tabsData['toStyle'];
		if ($toStyle < count($indices)) { // only redefine the styles to be added to the head if we actually need to generate extra styles.
			$toStyle = count($indices);
			$this->insertCSSJS($parser); // reload dynamic CSS with new amount
		}
		
		return "$form<div$attrStr>$labels<div class=\"tabs-container\" style=\"$containerStyle\">$newstr</div></div>";
	}
		
	/**
	 * Template for the tab label
	 * @param int $tabN The index of the individual tab.
	 * @param string $label The label that is going to appear to the user.
	 * @param int $tagN The index of the <tabs> tag on the page.
	 * @return string HTML code of the label
	 */
	public function makeLabel($tabN, $label, $tagN) {
		$label = htmlspecialchars($label);
		return "<input type=\"radio\" form=\"tabs-inputform\" id=\"tabs-input-$tagN-$tabN\" name=\"tabs-$tagN\" class=\"tabs-input tabs-input-$tabN\"/><label class=\"tabs-label\" for=\"tabs-input-$tagN-$tabN\" data-tabpos=\"$tabN\">$label</label>";
	}
	
	/**
	 * Filters list of entered parameters to only the HTML-safe attributes
	 * @param array $attr The full list of entered attributes
	 * [@param array $safe] The array in which to store the safe attributes
	 * @return array The list of safe attributes. Format: array(attrname => attrvalue)
	 */
	public function getSafeAttrs($attr, &$safe = array()) {
		$safeAttrs = array('class', 'id', 'title', 'style');
		$attrStr = '';
		// Apply width style if width attribute is defined, and no styles are defined OR width is not defined in the styles.
		$widhigh = array('width', 'height');
		foreach ($widhigh as $i) {
			$setStyles = isset($attr['style']) ? $attr['style'] : false;
			// If the attribute 'width' or 'height' is defined, AND either no styles have yet been set, OR those set stiles have no defined 'width' or 'height'.
			if (isset($attr[$i]) && (!$setStyles || !preg_match("/$i\s*:/", $setStyles))) {
				$whAttr = $attr[$i];
				$whAttr .= preg_match("/^\d+$/", $whAttr) ? 'px' : ''; // append px when no unit is defined
				// insert the 'width' or 'height' style at the start of the styles to prevent having to insert a semicolon at the end of the current style list, and possibly getting double semicolons.
				$attr['style'] = "$i: $whAttr; $setStyles";
			}
		}

		foreach ($safeAttrs as $i) {
			if (isset($attr[$i])) {
				$safe[$i] = htmlspecialchars(trim($attr[$i]));
				$attrStr .= " $i=\"".$safe[$i].'"';
			} else
				$safe[$i] = '';
		}
		return $attrStr;
	}
		
	/**
	 * Insert the static and dynamic CSS and JS into the page
	 * @param Parser $parser
	 * @return string Returns the form the input elements are assigned to via their form="" attribute for semantic purposes.
	 */
	public function insertCSSJS(&$parser) {
		$parserOut = $parser->getOutput();
		$parserOut->addHeadItem($this->createDynamicCss($parser), 'TabsStyles');
		if (!$parser->tabsData['addedStatics']) {
			$parser->tabsData['addedStatics'] = true;
			$parserOut->addModuleStyles('ext.tabs');
			$parserOut->addModuleScripts('ext.tabs');
			global $wgOut;
			// this form is here to use for the form="" attribute in the inputs, for semantically correct usage of the <input> tag outside a <form> tag.
			return '<form id="tabs-inputform" action="#"></form>';
		}
		return '';
	}

	public function createDynamicCss(&$parser) {
		$css = '';
		for ($i=1;$i<=$parser->tabsData['toStyle'];$i++) {
			$css .= ".tabs-input-$i:checked ~ .tabs-container .tabs-content-$i, .tabs-input-$i:checked ~ .tabs-container .tabs-content-$i,\n";
		}
		$css .= '.tabs-input-0:checked ~ .tabs-container .tabs-content-1, .tabs-input-0:checked ~ .tabs-container .tabs-content-1 {display:inline-block;}';
		$css .= "\n".str_replace(':checked','.checked', $css); // This is for the non-:checked browsers that use JS
		return "<style type=\"text/css\" id=\"tabs-dynamic-styles\">/*<![CDATA[*/\n/* Dynamically generated tabs styles */\n$css\n/*]]>*/</style>";
	}
}