function modifierFormulaireSuppression() {

	$('#suppression').bind('submit',function() {
		
	  // Si l'utilisateur confirme
	  mail = $('#mail_suppression').attr('value');
	  
	  if(mail != null && mail != undefined) {
		  message = 'Etes vous sur de vouloir supprimer votre inscription avec le compte '+mail+' ?'
	  } else {
		 message = 'Etes vous sur de vouloir supprimer votre inscription ?'; 
	  }
		
	  if(window.confirm(message)) {
	    // On récupère l'attribut action du formulaire
	    url_action = $('#suppression').attr("action");
	    // et on le change pour sauter la page du formulaire de suppression
	    url_action = url_action.replace('annuaire_formulaire_suppression_inscription', 'annuaire_suppression_inscription');
	    $('#suppression').attr("action", url_action);
	  } else {
		  return false;
	  }
	});
}

$('#suppression').ready(modifierFormulaireSuppression);
