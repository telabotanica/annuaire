function cocherDecocher(cocher) {	
	$("#resultat_recherche :checkbox").attr('checked', cocher);	
	return false;
}

function creerLiensCocherTout() {
	strLien = '<a id="cocher_tout" href=#> Tout cocher </a> / <a id="decocher_tout" href=#> Tout d&eacute;cocher </a>';
		
	if($('#conteneur_lien_cocher') != null) { 
		$('#conteneur_lien_cocher').html(strLien);
		
		$('#cocher_tout').bind('click',function() {
			cocherDecocher(true);
			return false;
		});
		
		$('#decocher_tout').bind('click',function() {
			
			cocherDecocher(false);
			return false;
		});
	}
}

$('#envoyer_tous').ready(function() { $('#envoyer_tous').click(function() {			
		cocher = $("#envoyer_tous").attr('checked');
		cocherDecocher(cocher);
	});
});
