$(function() {
	if ($('#tabs-inputform').css('font-family').replace(/["']/g,'') == 'sans-serif' && $('#tabs-inputform').css('margin') == '1px') {
	// Credit for this testing method: 2astalavista @ http://stackoverflow.com/a/21095568/1256925
		$(function() {
			$('body').addClass('tabs-oldbrowserscript'); // Make the unselected tabs hide when the browser loads this script
			$('.tabs-label').click(function(e) { $('#'+$(this).attr('for')).click(); e.preventDefault(); return false; });
			$('.tabs-input').each(function() {
				if (this.checked) $(this).addClass('checked'); // Adds checked class to each checked box
			}).change(function() {
				if (!this.checked) return $(this).removeClass('checked'); // for toggleboxes
				$(this).siblings('.checked').removeClass('checked'); // Uncheck all currently checked siblings
				$(this).addClass('checked'); // and do check this box
				$(this).parents('.tabs').toggleClass('tabs').toggleClass('tabs'); // remove and readd class to recalculate styles for its children.
				// Credit: Fabrício Matté @ http://stackoverflow.com/a/21122724/1256925
			});
			moveToHash();
		});
	} else
		$(moveToHash);
	
	/**
	 * Imitates the normal feature in browsers to scroll to an id that has the same id as the url fragment/hash.
	 * This makes it unnecessary to use actual ids on the tabs, which could cause the same id to occur twice in the same document.
	 * Does not scroll to exactly the tab's height, but just a bit above it.
	 */
	function moveToHash() {
		var hash = location.hash.substr(1).replace(/_/g,' ').trim();
		if (!hash || $(location.hash).length) return; // if there's no hash defined, or an element on the page with the same hash already, stop looking for tabs
		$('.tabs-tabbox .tabs-label:contains('+hash+')').each(function() {
			// double-check if the hash is indeed exactly the same as the label.
			// Does not match if hash is only a part of the label's contents, unlike jQuery's :contains() selector
			if (this.innerHTML.trim() !== hash) return true; // continue the $.each() function
			this.click(); // open the selected tab by default.
			document.documentElement.scrollTop = this.offsetTop;
			return false; // stop the $.each() function after the first match.
		});
	}
});