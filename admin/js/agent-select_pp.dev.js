function pp_load_agents_js( id_sfx, agent_type, context, agent_id, selection_only ) {
	jQuery(document).ready( function($) {
		$("#select_agents_" + id_sfx).click(function(e) {
			e.preventDefault();
			pp_select_agents(id_sfx);
		});
		
		$("#agent_results_" + id_sfx).bind('dblclick', function(e) { 
			e.preventDefault();
			pp_select_agents(id_sfx); 
		});
		
		if ( selection_only != true ) {
			$("#unselect_agents_" + id_sfx).bind('click', function(event) { pp_unselect_list_agents(id_sfx); });
			$("#" + id_sfx).bind('dblclick', function(event) { pp_unselect_list_agents(id_sfx); });

			$("#agent_results_" + id_sfx).DynamicListbox( { ajax_url:PPAgentSelect.adminurl, agent_type:agent_type, search_id:'agent_search_text_' + id_sfx, results_id:'agent_results_' + id_sfx, button_id:'agent_submit_' + id_sfx, ajaxhandler:PPAgentSelect.ajaxhandler, pp_context:context, topic:id_sfx, agent_id:agent_id } );
		}
	});
}

function build_selection_csv( list_id, $ ) {
	var s = '';

	$("#" + list_id + " option").each(function(){
		s = s + $(this).attr("value") + ',';
	}) ;
	
	$("#" + list_id + "_csv").attr("value", s);
}

function pp_select_agents( id_sfx, select_into, hierarchical ) {
	jQuery(document).ready( function($) {
		$("#agent_results_" + id_sfx + " option:selected").each(function(i){
			if ( $("#" + id_sfx + " option[value='" + $(this).attr("value") + "']").length == 0 ) {
				$("#" + id_sfx ).append('<option value="' + $(this).attr("value")+'" title="' + $(this).html() + '" class="pp-new-selection">' + $(this).html() + '</option>');
				$(this).remove();
			}
		});

		build_selection_csv( id_sfx, $ );
	});
}

function pp_unselect_list_agents( id_sfx ) {
	jQuery(document).ready( function($) {
		$("#" + id_sfx + " option:selected").appendTo("#agent_results_" + id_sfx);
		build_selection_csv( id_sfx, $ );
	});
}

