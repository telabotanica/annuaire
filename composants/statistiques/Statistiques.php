<?php
/**
* PHP Version 5
*
* @category  PHP
* @package   annuaire
* @author    aurelien <aurelien@tela-botanica.org>
* @copyright 2010 Tela-Botanica
* @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
* @version   SVN: <svn_id>
* @link      /doc/annuaire/
*/

class Statistiques {

	const GRAPH_CAMEMBERT = 'pie';
	const GRAPH_COURBE = 'courbe';
	private $chemin_lib_graph = '';
	
	public function __construct() {
		$this->chemin_lib_graph = Config::get('chemin_jpgraph');
	}

	public function genererGraphique($type_graphique, $valeurs, $titre = '', $taille = array(800, 800), $nom_axe_x = '', $nom_axe_y = '') {
		include_once $this->chemin_lib_graph.'jpgraph.php';
		
		$graph = null;
		switch($type_graphique) {
			case Statistiques::GRAPH_CAMEMBERT:
				$graph = $this->genererGraphiqueCamembert($valeurs, $titre, $taille);
				break;
			case Statistiques::GRAPH_COURBE:
				$graph = $this->genererGraphiqueCourbe($valeurs, $titre, $taille, $nom_axe_x, $nom_axe_y);
				break;
			default:
				$graph = $this->genererGraphiqueCourbe($valeurs);
		}

		return $graph;
	}

	public function genererGraphiqueCamembert($valeurs, $titre, $taille) {
		include_once $this->chemin_lib_graph.'jpgraph_pie.php';
		$legendes = array_keys($valeurs);
		$valeurs = array_values($valeurs);
		//die('<pre>'.print_r($valeurs, true).'</pre>');
		$oPie = new PiePlot($valeurs);
		$oPie->SetLegends($legendes);
		// position du graphique (légèrement à droite)
		$oPie->SetCenter(0.35);
		$oPie->SetValueType(PIE_VALUE_PER);
		// Format des valeurs de type "entier"
		$oPie->value->SetFormat('%1.2f%%');

		$graph = new PieGraph($taille[0],$taille[1]);
		// Ajouter le titre du graphique
		$graph->title->Set($titre);
		$graph->Add($oPie);
		return $graph;
	}

	public function genererGraphiqueCourbe($valeurs, $titre, $taille, $nom_axe_x, $nom_axe_y) {
		include_once $this->chemin_lib_graph.'jpgraph_line.php';
		
		// Création du conteneur
		$graph = new Graph($taille[0],$taille[1],"auto");

		$graph->img->SetMargin(50,30,50,100);   

		// Lissage sur fond blanc (évite la pixellisation)
		$graph->img->SetAntiAliasing("white");
		$graph->SetMarginColor("white");  

		// A détailler
		$graph->SetScale("textlin");

		// Ajouter une ombre
		$graph->SetShadow();

		// Ajouter le titre du graphique
		$graph->title->Set($titre);

		// Afficher la grille de l'axe des ordonnées
		$graph->ygrid->Show();
		// Fixer la couleur de l'axe (bleu avec transparence : @0.7)
		$graph->ygrid->SetColor('#E6E6E6@0.7');
		// Des tirets pour les lignes
		$graph->ygrid->SetLineStyle('solid');

		// Afficher la grille de l'axe des abscisses
		//$graph->xgrid->Show();
		// Fixer la couleur de l'axe (rouge avec transparence : @0.7)
		//$graph->xgrid->SetColor('red@0.7');
		// Des tirets pour les lignes
		//$graph->xgrid->SetLineStyle('solid');
		$graph->xaxis->SetLabelAngle(90);
		$graph->xaxis->SetTextLabelInterval(4);

		// Créer une courbes
		$courbe = new LinePlot(array_values($valeurs));

		// Chaque point de la courbe ****
		// Type de point
		$courbe->mark->SetType(MARK_FILLEDCIRCLE);
		// Couleur de remplissage
		$courbe->mark->SetFillColor("red");
		// Taille
		$courbe->mark->SetWidth(1);

		// Paramétrage des axes
		//$graph->xaxis->title->Set($nom_axe_x);
		$txt = new Text($nom_axe_x,270,460); 
		$graph->xaxis->SetTickLabels(array_keys($valeurs));

		// Paramétrage des axes
		$graph->yaxis->title->Set($nom_axe_y);

		// Ajouter la courbe au conteneur
		$graph->Add($courbe);

		return $graph;
	}

	public function combinerGraphiques($graph1, $graph2, $taille) {
		include_once $this->chemin_lib_graph.'jpgraph_mgraph.php';

		$mgraph = new MGraph($taille[0],$taille[1],"auto");
		$xpos1=300;$ypos1=3;
		$xpos2=3;$ypos2=200;
		
		$graph1->SetFrame(false);
		$graph2->SetFrame(false);
		
		//$xpos3=3;$ypos3=1000;
		$mgraph->Add($graph1,$xpos1,$ypos1);
		$mgraph->Add($graph2,$xpos2,$ypos2);
		$mgraph->SetShadow();
		//$mgraph->Add($graph['experience_bota'],$xpos3,$ypos3);
		return $mgraph;
	}
	
	public function dessinerGraph($graph) {
		return $graph->Stroke(_IMG_HANDLER);
	}
}
?>