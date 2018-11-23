jQuery('.cid-homepage-hide').add('.cid-navigation-item').add('.category-list').add('.cid-action-icons').hide();
jQuery('.cid-navigation-item a').removeAttr('href').add('.parent-category-button').click(setCidparent);

// Set hidden input for search form
function setCidparent() {
	var cidparentInput = jQuery('.cid-search input[name="cidparent"]');
	var parentId = jQuery(this).attr('data-parent-category-id');
	if(cidparentInput.length == 0) {
		jQuery('.cid-search form').append('<input name="cidparent" type="hidden" value="' + parentId + '">');
	} else {
		cidparentInput.attr('value', parentId);
	}
}