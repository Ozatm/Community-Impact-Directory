var cidParent = autohide_ajax_object.cidparent;

jQuery('.cid-ad-hide').hide();

if(cidParent != -1) {
	jQuery('.cid-ad-show').hide();
	jQuery('.cid-ad-hide.cid-parent-' + cidParent).show();
}