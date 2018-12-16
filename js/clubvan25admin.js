function wpAjax(action, data, onResponse) {
	jQuery.post(
		ajaxurl, 
		{
			'action': action,
			'data':   data,
		}, 
		function(response){
			onResponse(response);
		}
	);
}

function wpAjaxLoad(id, action, data, onResponse) {
	jQuery("#" + id).load(
		ajaxurl, 
		{
			'action': action,
			'data':   data,
		},
		function(response) {
			onResponse(response);
		}
	);
}

function updateYear() {	
	var year = jQuery('#' + php.currentYear).val();
		
	jQuery('#' + php.currentButton).prop('disabled', true);
	jQuery('#' + php.currentSpinner).css('display', 'inline-block');
	
	wpAjax(php.currentAction, { 'year': year }, function(response) {
		var response = jQuery.parseJSON(response);
				
		jQuery('#' + php.currentYear).val(response.year);		
		jQuery('#' + php.currentButton).prop('disabled', false);
		jQuery('#' + php.currentSpinner).css('display', 'none');
	});	
}

function loadYears(year, done) {
	wpAjax(php.yearsAction, null, function(response) {
		var result = jQuery.parseJSON(response);
		var years = result.years;
		var currentYear = result.current;
		
		// when creating a new year, add it to both selects
		if (year && years.indexOf(year) < 0) {
			years.push(year);
		}
		
		var selectIds = ['#' + php.currentYear, '#' + php.memberYear];
		
		for (var j = 0; j < selectIds.length; j++) {
			var selectId = selectIds[j];
			
			var select = jQuery(selectId);
		
			jQuery(selectId + ' option').remove();
			for (var i = 0; i < years.length; i++) {
				select.append(jQuery('<option></option>').text(years[i]).val(years[i]));
			}
			select.val(currentYear);
		}
		
		var newYear = parseInt(years[years.length-1]) + 1 || 2000;
		jQuery('#new-year-id').val(newYear);
		
		if (done) {
			done();
		}
	});
}

function loadMemberTable(setYear) {
	if (setYear !== undefined) {
		jQuery('#' + php.memberYear).val(setYear);
	}
	
	var year = jQuery('#' + php.memberYear).val();

	jQuery('#' + php.memberButton).prop('disabled', true);
	jQuery('#' + php.memberSpinner).css('display', 'inline-block');
		
	wpAjaxLoad(php.memberTable, php.memberAction, { 'year' : year }, function(response) {
		jQuery('#' + php.memberButton).prop('disabled', false);
		jQuery('#' + php.memberSpinner).css('display', 'none');
	});
}

function addMember(form) {
	var year = jQuery('#' + php.memberYear).val();
	
	var formData = jQuery(form).serializeArray();
	var data = {};
	formData.forEach(function(item) {
		data[item.name] = item.value;
	});
	data.year = year;
	
	jQuery('#' + php.newMemberButton).prop('disabled', true);
	jQuery('#' + php.newMemberSpinner).css('display', 'inline-block');
	
	wpAjax(php.createMemberAction, data, function(response) {
		jQuery('#' + php.newMemberButton).prop('disabled', false);
		jQuery('#' + php.newMemberSpinner).css('display', 'none');
		
		form.reset();
		loadMemberTable();
	});
}

function addNewYear() {
	var year = jQuery('#new-year-id').val();
	var ids = getSelectedCheckboxes();
	
	if (ids.length === 0) {
		if (!confirm('Je hebt geen namen geselecteerd om over te nemen van het vorige jaar. Weet je zeker dat je dat de bedoeling is? Je zal ze dan één voor één moeten toevoegen.')) {
			return;
		}
	}
	
	var data = {
		year: year,
		ids: ids,
	};
	
	wpAjax(php.newYearAction, data, function(response) {
	 	loadYears(year, function() {
			loadMemberTable(year);
		});
	});
}

function deleteMembers() {
	var ids = getSelectedCheckboxes();
	
	if (ids.length > 0) {
		if (confirm('Weet je zeker dat je ' + (ids.length === 1 ? 'dit lid' : ' deze ' + ids.length + ' leden')+ ' wil verwijderen?')) {
			wpAjax(php.deleteMembers, ids, function(response) {
				loadMemberTable();
			});
		}
	}
}

function getAllCheckboxes() {
	return jQuery('.member-checkbox');
}

function selectAll(checked) {
	var checkboxes = getAllCheckboxes();
	for (var i = 0; i < checkboxes.length; i++) {
		checkboxes[i].checked = checked;
	}
}

function getSelectedCheckboxes() {
	var result = [];
	var checkboxes = getAllCheckboxes();
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].checked) {
			result.push(checkboxes[i].value);
		}
	}
	return result;
}

function selectMember(checked) {
	var allChecked = true;
	if (checked) {
		var checkboxes = getAllCheckboxes();
		for (var i = 0; i < checkboxes.length; i++) {
			if (!checkboxes[i].checked) {
				allChecked = false;
				break;
			}
		}
	} else {
		allChecked = false;
	}
	
	jQuery('#member-select-all-checkbox')[0].checked = allChecked;
}