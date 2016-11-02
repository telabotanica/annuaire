//TODO: les identifiants des champs pourraient être générés par leur 
// code alphanumérique plutot que leur clé ce qui rendrait la compréhension
// de ce genre de code bien plus facile
$(document).ready(function() {
	// lettre d'actualité cochée par defaut
	$("#lettre_14").attr('checked', true);	
	
	// Le champ département n'a de sens que s'il l'on
	// selectionne la France (mise à 0 si autre pays)
	gererAffichageChampDepartement();
	$("#select_12").change(function() {
		gererAffichageChampDepartement();
	});
});

function gererAffichageChampDepartement() {
	console.log($("#select_12").val());
	// TODO: pour des listes déjà existantes, utiliser les id standards
	// (comme des codes de pays, au lieu de clés auto incrémentée
	if($("#select_12").val() == "2654") {
		// 2654 correspond à la france
		$("#text_13").parent().show();
		$("#text_13").parent().next("br").show();
		$("#text_13").val("");
	  } else {
		$("#text_13").parent().hide();
		$("#text_13").parent().next("br").hide();
		$("#text_13").val(0);
	  }
}