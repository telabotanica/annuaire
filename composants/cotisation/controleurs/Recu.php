<?php
// Création de l'objet pdf

class Recu extends AppControleur {
	
	public function Recu() {

		require_once('bibliotheque/tcpdf/config/lang/fra.php');
		require 'bibliotheque/tcpdf/tcpdf.php';
		require 'bibliotheque/Words/Words.php';
		// Constante nécessaire à fpdf.php
		parent::__construct();
	}

	public function fabriquerRecuPdf($utilisateur, $cotisation) {
	
		$num_recu = $cotisation['recu_envoye'];
		
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		//$pdf->Open();
		$pdf->SetTitle('Recu pour don à Tela Botanica');
		
		$pdf->SetFont('helvetica','',14);
		
		$pdf->AddPage();
		
		$pdf->Line(10, 10, 200, 10) ;

		// Contenu du document
		
		$pdf->SetFont('helvetica', '', 8) ;
		
		$pdf->Cell(150, 10, "", 0, 0) ;
		
		$pdf->MultiCell(40, 10, "Numéro d'ordre : $num_recu", 1, "C") ;
		
		$pdf->SetY($pdf->GetY() - 10) ;
		
		$pdf->SetFont('helvetica','B',14);
		$pdf->Cell(0,10,'Reçu dons aux oeuvres', 0, 1, "C");
		$pdf->SetFont('helvetica', '', 10) ;
		$pdf->Cell(0, 0, 'Articles 200, 238 bis et 885-0 du code général des impôts (CGI)', 0, 1, "C") ;
		
		$pdf->Cell(0, 10, 'REÇU A CONSERVER ET A JOINDRE A VOTRE DECLARATION DE REVENUS '.$cotisation['annee_cotisation'], 0, 1, "L") ;
		
		// On met le logo de Tela
		$pdf->Image(dirname(__FILE__)."/logo_tela.png", 12, 40, "29", "", "PNG", "http://www.tela-botanica.org/") ;
		
		// On écrit Les titres du cadre
		$pdf->SetFontSize(12) ;
		$pdf->Cell(100, 10, 'Bénéficiaire du don', 0, 0, "C") ;
		$pdf->Cell(100, 10, 'Donateur', 0, 1, "C") ;
		
		$pdf->Cell(38, 5, '', 0, 0) ;
		$pdf->Cell(62, 5, 'Association Tela Botanica', 0, 0, "L") ;
		
		$pdf->SetFont('helvetica', 'B', 10) ;
		
		$pdf->Cell(100, 5, $utilisateur['nom']['amv_valeur'].' '.$utilisateur['prenom']['amv_valeur'], 0, 1, "L") ;
		
		$pdf->SetFont('helvetica', '', 10) ;

		$pdf->Cell(38, 5, '', 0, 0) ;
		$pdf->Cell(62, 5, '4, rue de Belfort', 0, 0, "L") ;
		$pdf->Cell(100, 5, $utilisateur['adresse']['amv_valeur'], 0, 1, "L") ;
				
		$pdf->Cell(38, 5, '', 0, 0) ;
		$pdf->Cell(62, 5, '34090 Montpellier', 0, 0, "L") ;
		$pdf->Cell(100, 8, $utilisateur['adresse_comp']['amv_valeur'], 0, 1, "L") ;
		
		
		$pdf->Cell(100, 5, 'Objet :', 0,1, "L") ;
		$pdf->SetFontSize(8) ;
		$pdf->MultiCell(100, 4, 'Contribuer au rapprochement de tous les botanistes de langue française. Favoriser l\'échange d\'information'.
		                        ' et animer des projets botaniques grâce aux nouvelles technologies de la communication.', 0, 1, "") ;
		$pdf->MultiCell(100,4, 'Organisme d\'intérêt général à caractère scientifique concourant à la diffusion de la langue et des connaissances scientifiques françaises.', 0,1, "") ;
		
		$pdf->SetFontSize(10) ;
		
		$pdf->Text(111, 58 + 8, $utilisateur['code_postal']['amv_valeur'].' '.$utilisateur['ville']['amv_valeur']) ;
		$pdf->SetFontSize(8) ;
		
		// On remonte le curseur de 52
		$pdf->SetY($pdf->GetY() - 30) ;
		
		// Le cadre central
		$pdf->Cell(100, 60, '', 1) ;
		$pdf->Cell(90, 60, '', 1) ;
		$pdf->Ln() ;
		
		$pdf->SetFontSize(10) ;
		$pdf->Cell(0,10, 'L\'Association reconnaît avoir reçu en numéraire, à titre de don, la somme de :', 0, 1, "L") ;
		
		$wordsConverter = new Numbers_Words() ;
		$montantLettres = $wordsConverter->toWords($cotisation['montant_cotisation'],'fr') ;
				
		$pdf->SetFont('helvetica', 'B', 11) ;
		$pdf->Cell(0,10,  "*** ".$cotisation['montant_cotisation']." euros ***", 0, 1, "C") ;
		$pdf->Ln() ;
		$pdf->Cell(0,10,  "*** (".$montantLettres." euros) ***", 0, 1, "C") ;
		
		$pdf->SetFont('helvetica', '', 10) ;
		
		$pdf->Ln() ;
		$pdf->Cell(100,10, "Date du paiement : ".$cotisation['date_cotisation'], 0, 0, "L") ;
		$pdf->Cell(100, 10, 'Montpellier, le '.$cotisation['date_envoi_recu'], 0, 1, "L") ;
				
		// La signature de Daniel Mathieu
		$pdf->Image(dirname(__FILE__).'/signature_Daniel.png', 110, $pdf->GetY(),28.22, "") ;
				
		$pdf->Ln() ;
		$pdf->Cell(0, 10, "Mode de versement : ".$cotisation['mode_cotisation'], 0, 1, "L") ;
		
		$pdf->Cell(100, 10, '', 0, 0) ;
		$pdf->Cell (100, 10, 'Daniel Mathieu, Président', 0, 1, "L") ;
		$pdf->Ln(5) ;
		
		$pdf->SetFontSize(10) ;
		$pdf->Cell(0, 7, '66 % de votre don à Tela Botanica est déductible de vos impôts dans la limite de 20 % de votre revenu imposable.', 1, 1, "C") ;
	
		return $pdf;
	}
	
	public function afficherRecuPdf($utilisateur, $cotisation) {
		
		$pdf = $this->fabriquerRecuPdf($utilisateur, $cotisation);
		$pdf->Output();
		exit;
	}
	
	public function renvoyerRecuPdf($utilisateur, $cotisation) {
		
		$pdf = $this->fabriquerRecuPdf($utilisateur, $cotisation);
		
		$contenu_pdf = $pdf->Output('','S');
		
		return $contenu_pdf;
	}
}
?>