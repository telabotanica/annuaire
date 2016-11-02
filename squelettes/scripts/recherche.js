$("#option_2654").ready(function() {
	$("#option_2654").attr('selected', 'selected');	
});

$('.resultat_recherche .element_resultat .cliquable').ready(function() {
	$('.resultat_recherche .element_resultat').click(function() {
	      var lien = $(this).find("a").attr("href");
	      location.href = lien;
	});
});