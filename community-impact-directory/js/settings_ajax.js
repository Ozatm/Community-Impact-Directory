if(ajax_object.page == 'directory_default_logo') {
	jQuery('.select_image_button').click( function(){
		
		// Create the media frame.
		var file_frame = wp.media.frames.file_frame = wp.media({
			title: 'Select icon',
			button: {
				text: 'Use this image',
			},
			multiple: false	// Only allow one image to be selected
		});
		
		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
			var attachment = file_frame.state().get('selection').first().toJSON();
			jQuery('#directory_default_logo_img').attr( 'src', attachment.url );
			jQuery('#directory_default_logo').attr( 'value', attachment.id );
			
			var index = actionsArray.findIndex(function(icon){
				return icon.icon_id == iconId;
			});
			actionsArray[index]['icon_src'] = attachment.url;
		});
		
		// Open the modal
		file_frame.open();
	});
	
	jQuery('.clear_image_button').click(function() {
		jQuery('#directory_default_logo_img').attr( 'src', '' );
		jQuery('#directory_default_logo').attr( 'value', '' );
	});
} else {

	var actionsArray = ajax_object.directory_actions;
	if(!Array.isArray(actionsArray)) {
		actionsArray = [];
	}

	actionsArray = actionsArray.filter(icon => icon != '');

	// Choose icon image
	jQuery('.select_image_button').click( function( event ){
		selectIcon(this);
	});

	function selectIcon (button_clicked) {
		var iconId = jQuery(button_clicked).attr('data-icon-id');
		
		// Create the media frame.
		var file_frame = wp.media.frames.file_frame = wp.media({
			title: 'Select icon',
			button: {
				text: 'Use this image',
			},
			multiple: false	// Only allow one image to be selected
		});
		
		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
			var attachment = file_frame.state().get('selection').first().toJSON();
			jQuery('img[data-icon-id='+ iconId +']').attr( 'src', attachment.url );
			var index = actionsArray.findIndex(function(icon){
				return icon.icon_id == iconId;
			});
			actionsArray[index]['icon_src'] = attachment.url;
		});
		
		// Open the modal
		file_frame.open();
	}

	// Add delete function to original buttons
	jQuery('.delete_icon_type_button').click(function(e){
		deleteIconType(this);
	});

	var deleteArray = [];

	function deleteIconType (button) {		
		var iconId = jQuery(button).attr('data-icon-id');
		var index = actionsArray.findIndex(function(icon){
			return icon.icon_id == iconId;
		});
		
		deleteArray[iconId] = {domElement: jQuery(button).closest('tr').detach(), arrayElement: actionsArray[index]};
		jQuery('#recover-icons').append('<li data-icon-id="'+iconId+'">'+actionsArray[index]['icon_name']+' Icon<input type="button" value="Restore" class="restore-button" data-icon-id="'+iconId+'" data-icon-index="'+index+'"></li>');
		
		jQuery('.restore-button[data-icon-id="'+iconId+'"]').click(function() {
			var iconId = jQuery(this).attr('data-icon-id');
			var index = jQuery(this).attr('data-icon-index');
			var restoreElement = deleteArray[iconId]['domElement'];
			
			if(actionsArray.length == 0 || index == 0) {
				jQuery(this).closest('form').find('tbody').prepend(restoreElement.get(0));
				actionsArray.splice(0, 0, deleteArray[iconId]['arrayElement']);
			} else {
				if(index > actionsArray.length) {
					index = actionsArray.length;
				}
				
				jQuery('.position_number')[index - 1].closest('tr').after(restoreElement.get(0));
				actionsArray.splice(index, 0, deleteArray[iconId]['arrayElement']);
			}
			
			delete deleteArray[iconId];
			setupPositionNumbers();
			jQuery(this).closest('li').remove();
		});
		
		actionsArray.splice(index, 1);
		setupPositionNumbers();
	}
		
	// Add new icon type to display
	jQuery('#add_icon_type_button').click(function(){
		var name = jQuery('<div />').text(jQuery('#add_icon_type').val()).html();
		var newId = 0;
		
		var deleteKeys = Object.keys(deleteArray);
		while(actionsArray.findIndex(value => newId == value.icon_id) != -1 || deleteKeys.findIndex(value => newId == value) != -1){
			newId++;
		}
		
		var newIcon = { 'icon_name': name, 'icon_src': '', 'icon_id': newId};
		actionsArray.push(newIcon);
			
		if(!jQuery.contains(document.getElementById('icon_management'), document.querySelector('#icon_management tbody'))) {
			jQuery('#icon_management').append('<table class="form-table"><tbody></tbody></table>');
		}
		
		var parentCategories = '';
		
		if(ajax_object.page == 'action_icons') {
			parentCategories = '<h4>Parent Categories</h4>';
		
			jQuery.each(ajax_object.parent_category_names, function(parentCategory, parentCategoryName) {
				parentCategories += '<label class="parent-category-label">' + parentCategoryName + ' (' + parentCategory + ')<input class="parent-category-checkbox" type="checkbox" data-icon-id="' + newId + '" value="' + parentCategory + '" checked></label>';
			});
		}
		
		jQuery('#icon_management tbody').append('<tr><th scope="row">'+ name +' Icon ('+newId+')</th><td><label class="position-label">Position: <input type="number" class="position_number" data-icon-id="'+newId+'"></label><input class="change_position_button" type="button" value="Change Position" data-icon-id="'+newId+'"><img class="image_preview" data-icon-id="'+newId+'"><input class="select_image_button" type="button" value="Select Icon" data-icon-id="'+newId+'"><input class="delete_icon_type_button" type="button" value="Delete Icon Type" data-icon-id="'+newId+'"><label class="edit-label">Display Name: <input type="text" class="edit_name_text" data-icon-id="'+newId+'"></label><input class="edit_name_button" type="button" value="Edit Name" data-icon-id="'+newId+'">' + parentCategories + '</td></tr>');
		jQuery('#add_icon_type').val('');
		jQuery('#icon_management .delete_icon_type_button').last().click(function(e) {
			deleteIconType(this);
		});
		jQuery('#icon_management .select_image_button').last().click(function(e) {
			selectIcon(this);
		});
		jQuery('#icon_management .edit_name_button').last().click(function(e) {
			editIconTypeName(this);
		});
		jQuery('#icon_management .edit_name_text').last().keypress(function(e) {
			editKeypress(e);
		});
		jQuery('#icon_management .change_position_button').last().click(function(e) {
			changePosition(this);
		});
		jQuery('#icon_management .position_number').last().keypress(function(e) {
			positionKeypress(e);
		});	
		setupPositionNumbers();
		jQuery('#add_icon_type').focus();
	});

	jQuery('#add_icon_type').keypress(function(e) {
		if(e.keyCode == 13) {
			e.preventDefault();
			jQuery('#add_icon_type_button').click();
		}
	});

	// Setup Change Position button
	function setupPositionNumbers() {
		jQuery('.position_number').each(function(index) {
			thisNumber = jQuery(this);
			thisNumber.val(index + 1);
			thisNumber.attr({min: 1, max: (actionsArray.length)});
		});
	}
	setupPositionNumbers();

	jQuery('.change_position_button').click(function(e){
		changePosition(this);
	});

	function changePosition (button) {
		var iconId = jQuery(button).attr('data-icon-id');
		var oldIndex = actionsArray.findIndex(function(icon){
			return icon.icon_id == iconId;
		});
		var newIndex = jQuery('.position_number[data-icon-id="'+iconId+'"]').val() - 1;
		var tempIcon = actionsArray[oldIndex];
		
		actionsArray.splice(oldIndex, 1);
		actionsArray.splice(newIndex, 0, tempIcon);
		
		var rows = jQuery(button).closest('tbody').children();
		var tempElement = jQuery(button).closest('tr').detach();
		
		if(rows.length - 1 == newIndex) {
			jQuery('.position_number')[newIndex - 1].closest('tr').after(tempElement.get(0));
		} else {
			jQuery('.position_number')[newIndex].closest('tr').before(tempElement.get(0));
		}
		
		jQuery('.position_number').each(function (index) {
			jQuery(this).val(index + 1);
		});
	}

	function positionKeypress(e) {
		if(e.keyCode == 13) {
			e.preventDefault();
			var iconId = jQuery(e.target).attr('data-icon-id');
			jQuery('.change_position_button[data-icon-id="'+iconId+'"]').click();
		}
	}

	jQuery('.position_number').keypress(function(e) {
		positionKeypress(e);
	});

	// Setup Edit Name button
	jQuery('.edit_name_button').click(function(e){
		editIconTypeName(this);
	});

	function editIconTypeName (button) {
		var iconId = jQuery(button).attr('data-icon-id');
		var index = actionsArray.findIndex(function(icon){
			return icon.icon_id == iconId;
		});
		actionsArray[index]['icon_name'] = jQuery('.edit_name_text[data-icon-id="'+iconId+'"]').val();
		jQuery(button).closest('tr').children('th').html(actionsArray[index]['icon_name'] + " Icon");
		jQuery('.edit_name_text[data-icon-id="'+iconId+'"]').val('');
	}

	function editKeypress(e) {
		if(e.keyCode == 13) {
			e.preventDefault();
			var iconId = jQuery(e.target).attr('data-icon-id');
			jQuery('.edit_name_button[data-icon-id="'+iconId+'"]').click();
		}
	}

	jQuery('.edit_name_text').keypress(function(e) {
		editKeypress(e);
	});

	// Setup submit button
	jQuery('#settings-saved').hide();
	jQuery('.notice-dismiss').click(function() {jQuery('#settings-saved').hide();});

	jQuery('#submit').click(function(e){
		e.preventDefault();

		if(ajax_object.page == 'action_icons') {
			// Add parent categories
			jQuery.each(actionsArray, function(index, icon) {
				actionsArray[index]['icon_parents'] = jQuery('.parent-category-checkbox[data-icon-id="' + icon.icon_id + '"]:checked').map(function() {return jQuery(this).val();}).get();
			});
		}
		
		// Pass data through AJAX
		var data = {
			'page': ajax_object.page,
			'action': 'action_icon_update',
			'action_options': actionsArray,
			'deleted_icons': Object.keys(deleteArray)
		};
		jQuery.post(ajax_object.ajax_url, data, function(response){
			jQuery('#settings-saved').show();
		});
		
		jQuery('#recover-icons li').remove();
		deleteArray = [];
	});
}