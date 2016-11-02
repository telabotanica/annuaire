<?  // fichiers inclus
  require_once "Auth/OpenID/Consumer.php";
  require_once "Auth/OpenID/FileStore.php";

  // démarrage de la session (requis pour YADIS)
  session_start();

  // crée une zone de stockage pour les données OpenID
  $store = new Auth_OpenID_FileStore('./oid_store');

  // crée un consommateur OpenID
  $consumer = new Auth_OpenID_Consumer($store);

  // commence le process d'authentification
  // crée une requête d'authentification pour le fournisseur OpenID
  $auth = $consumer->begin($_POST['id']);
  if (!$auth) {
    die("ERROR: Entrez un OpenID valide svp.");
  }

  // redirige vers le fournisseur OpenID pour l'authentification
  $url = $auth->redirectURL('http://consumer.example.com/', 'http://consumer.example.com/oid_return.php');
  header('Location: ' . $url);

// fichiers inclus
require_once "Auth/OpenID/Consumer.php";
require_once "Auth/OpenID/FileStore.php";
require_once "Auth/OpenID/SReg.php";

// démarrage de session (requis pour YADIS)
session_start();

// crée une zone de stockage pour les données OpenID
$store = new Auth_OpenID_FileStore('./oid_store');

// crée un consommateur OpenID
// lit la réponse depuis e fournisseur OPenID
$consumer = new Auth_OpenID_Consumer($store);
$response = $consumer->complete('http://consumer.example.com/oid_return.php');

// crée une variable de session qui dépend de l'authentification
if ($response->status == Auth_OpenID_SUCCESS) {
  $_SESSION['OPENID_AUTH'] = true;

  // récupère les informations d'enregistrement
 $sreg = new Auth_OpenID_SRegResponse();
  $obj = $sreg->fromSuccessResponse($response);
  $data = $obj->contents();

  if (isset($data['email'])) {
    // Si l'adresse mail est disponible
    // Vérifie si l'utilisateur a déjà un compte sur le système

    // ouvre une connexion a la base
    $conn = mysql_connect('localhost', 'user', 'pass') or die('ERROR: Connexion serveur impossible');
    mysql_select_db('test') or die('ERROR: Impossible de sélectionner une base');

    // exécute la requête
    $result = mysql_query("SELECT DISTINCT COUNT(*) FROM users WHERE email = '" . $data['email'] . "'") or die('ERROR: La requête ne peut pas être exécutée');

    $row = mysql_fetch_array($result);
    if ($row[0] == 1) {
      // si oui affiche un message personnalisé
      $newUser = false;
      echo 'Bonjour et bienvenue, ' . $data['email'];
      exit();
    } else {
      // si non avertit que l'utilisateur est nouveau
      $newUser = true;
    }

    // ferme la connexion
    mysql_free_result($result);
    mysql_close($conn);
  } else {
    // si l'adresse email n'est pas disponible
    // avertit que l'utilisateur est nouveau
 $newUser = true;
  }
} else {
  $_SESSION['OPENID_AUTH'] = false;
  die ('Vous n'avez pas la permission d'accéder a cette page! Re-loggez vous svp.');
}

?>