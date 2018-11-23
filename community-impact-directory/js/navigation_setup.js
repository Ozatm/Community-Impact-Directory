function displayButtons(cidParentId) {
		
	// Show only the selected parent associated category list
	jQuery('.parent-category-button').add('.category-list').add('.cid-action-icons').hide();
	jQuery('.category-list').add('.cid-action-icons').filter('[data-parent-category-id="' + cidParentId + '"]').show();
	
	// Show all other parents in the navigation
	jQuery('.cid-navigation-item').show();
	jQuery('.cid-navigation-item').has('a[data-parent-category-id="' + cidParentId + '"]').hide();
	
	// Show associated text
	jQuery('.cid-associated-text').hide();
	jQuery('.cid-parent-' + cidParentId).show();
	
}

jQuery('.parent-category-button').click(function() {
	
	// Add class for additional styles to show that a selection has been made
	jQuery('.parent-category-button').addClass('selected').off();
	
	// Hide items initally shown and show items initially hidden
	jQuery('.cid-homepage-hide').show();
	jQuery('.cid-homepage-show').hide();
	
	displayButtons(jQuery(this).attr('data-parent-category-id'));
	
});

jQuery('.cid-navigation-item').click(function() {
	displayButtons(jQuery(this).children('a').attr('data-parent-category-id'));
});
	