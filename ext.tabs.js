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
			moveToTarget(); // this must run after the change event handler has been added, otherwise browsers without :not() support won't change it.
		});
	} else if (document.getElementById(decodeURIComponent(document.location.hash.substr(1)))) 
		$(moveToTarget);
	
	function moveToTarget() {
		// TODO: #hash -> label -> data-tabpos -> contentdiv
	}
});