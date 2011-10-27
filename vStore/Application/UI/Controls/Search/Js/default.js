$(function() {

	var initVal = 'Hledat...',
		emptyVal = '',
		searchInput = $("input[type=text].search"),
		queryCache = [];
	searchInput.focus(function() {
		var $this = $(this);
		if($this.val() === initVal)
			$this.val(emptyVal);
	}).blur(function() {
		var $this = $(this);
		if($this.val() === '')
			$this.val(initVal);
	});
	searchInput.autocomplete({
		source: function(request, response) {
			if (queryCache[request.term]) {
				response(queryCache[request.term]);
			} else {
				$.ajax({
					url: searchInput.attr('data-autocomplete'),
					dataType: "json",
					data: {
						'search-query': request.term
					},
					success: function(data) {
						if (data.emptyResult === true) {
							response([{
								label: 'Na zadané klíčové slovo nebyl nalezen žádný výsledek',
								value: '',
								emptyResult: true
							}]);
						} else {
							var result = $.map(data.prompt, function(item) {
								return {
									label: item.title,
									image: item.imageUrl,
									value: item.title,
									link: item.link
								};
							});
							queryCache[request.term] = result;
							response(queryCache[request.term]);
						}
					}
				});
			}
		},
		select: function (e, ui) {
			if (ui.item.emptyResult) {
				return;
			}
			window.location.href = ui.item.link;
		}
	}).data( "autocomplete" )._renderItem = function( ul, item ) {
		return $( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( "<a class=\"searchAutocompleteItem\"><div style=\"background: url('" + item.image + "') no-repeat 6px center;" + (item.image == undefined ? " padding: 0 !important;" : "") + "\">" + item.label + "</div></a>" )
			.appendTo( ul );
	};
	
	searchInput.focus(function (e) {
		$(this).autocomplete('search');
	});
	searchInput.closest('form').submit(function (e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		return false;
	});
	
});