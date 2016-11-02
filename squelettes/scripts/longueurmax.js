function verifierLongueurMax()
{
	max = $(this).attr('maxlength');
	if(max != undefined && max != null) {
		$(this).val($(this).val().substring(0,max));
	}
}


function initialiserTextAreaLongueursMax() { 
	$('textarea.annuaire').bind('keypress',verifierLongueurMax);
	$('textarea.annuaire').bind('keyup', verifierLongueurMax);
	$('textarea.annuaire').bind('blur', verifierLongueurMax);
}

$(document).ready(initialiserTextAreaLongueursMax);

