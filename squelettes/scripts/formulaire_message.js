$('#envoi_message').ready(function() {
	
	$('#envoi_message').submit(function() {	
		var debut_message = 'Votre message n\'a pas pu \u00EAtre envoy\u00E9 pour les raisons suivantes : '+"\n";
		var message = '';
		
		if($('#envoyer_tous').attr('checked') == false && $('.selection_destinataire:checked').length < 1) {
			message += '- Vous n\'avez s\u00E9l\u00E9ctionn\u00E9 aucun destinataire '+"\n";
			$('#sujet_message').addClass('erreur_champ');
		}
		
		if($('#sujet_message').val() == '') {
			message += '- Le sujet du message est vide '+"\n";
			$('#sujet_message').addClass('erreur_champ');
		}
		
		if($('#contenu_message').val() == '') {
			message += '- Le contenu du message est vide '+"\n";
			$('#contenu_message').addClass('erreur_champ');
		}
		
		if(message != '') {
			window.alert(debut_message+message);
			return false;
		} else {
			return true;
		}
		
	});
});