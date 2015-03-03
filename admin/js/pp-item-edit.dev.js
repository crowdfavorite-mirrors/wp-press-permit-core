jQuery(document).ready( function($) {
	//$('li.agp-agent a').live('click',function() {  // @todo: merge these classes, slicker selectors
	$(document).on('click', 'li.agp-agent a', function() {
		$(this).closest('div.inside').find('li.agp-agent').removeClass( 'agp-selected_agent' ).removeClass( 'agp-selected_agent_colorized' ).addClass( 'agp-unselected_agent' ).addClass( 'agp-unselected_agent_colorized' );
		$(this).parent().addClass( 'agp-selected_agent' ).addClass( 'agp-selected_agent_colorized' ).removeClass( 'agp-unselected_agent' ).removeClass( 'agp-unselected_agent_colorized' );
		$(this).closest('div.inside').find('div.pp-agents > div').hide();
		pp_show_elem( agp_escape_id($(this).attr('class')), $ );
	});
});

// ensure selected dropdown option is styled according to its css class
jQuery(document).ready( function($) {
	//$( '.pp-exceptions select').live('change', function(e) {
	$(document).on('change', '.pp-exceptions select', function(e) {
		$(e.target.options).filter(":selected").each( function() {
			var elemclass = $(this).attr('class');
			if ( elemclass )
				$(this).parent().attr('class', elemclass);
			else
				$(this).parent().attr('class', '');
		});
	});
	
	$('.pp-exception-actions a.pp-select-exception-agents').click( function() {
		$(this).closest('tbody').find('td.pp-select-exception-agents').show();
		$(this).hide();
		$(this).parent().find('a.pp-close-select-exception-agents').show();
		$(this).closest('tbody').find('td.pp-select-exception-agents input').focus();
		
		$(this).closest('tbody').find('td.pp-current-item-exceptions div').scrollTop(0);
		return false;
	});
	
	$('.pp-exception-actions a.pp-close-select-exception-agents').click( function() {
		$(this).closest('tbody').find('td.pp-select-exception-agents').hide();
		$(this).hide();
		$(this).parent().find('a.pp-select-exception-agents').show();
		return false;
	});
	
	//$('td.pp-exc-item select').live('change', function() {
	$(document).on('change','td.pp-exc-item select',function() {
		$(this).closest('tr').find('td.pp-exc-children select[disabled="disabled"]').val( $(this).val() ).trigger('change');
		$(this).closest('tr').find('td.pp-exc-children input[type="hidden"]').val( $(this).val() );
	});
	
	// remove search result items for agents who have item exception UI dropdowns
	$('.pp-agents-selection select').bind('jchange', function() {
		var tree = $("<div>" + $(this).html() + "</div>");
		
		$(this).closest('table.pp-item-exceptions-ui').find('td.pp-current-item-exceptions td input[type="hidden"]').each( function(i,item) {
			tree.find('option[value="' + $(item).val() + '"]').remove();
		});
		
		$(this).html( tree.html() );
	});
	
	$('a[href="#clear-item-exc"]').click( function() {
		$(this).closest('table tbody').find('td.pp-exc-item select').val('').change();
		return false;
	});
	
	$('a[href="#clear-sub-exc"]').click( function() {
		$(this).closest('table tbody').find('td.pp-exc-children select').val('').change();
		return false;
	});
});