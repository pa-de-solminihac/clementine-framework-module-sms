<?php
/**
 * smsSmsHelper : module sms qui fonctionne avec smsmode.com
 * 
 * @package 
 * @version $id$
 * @copyright 
 * @author Pierre-Alexis <pa@quai13.com> 
 * @license 
 */
class smsSmsHelper extends smsSmsHelper_Parent
{

    public $classes_msg = array(
        'pro'     => 2,
        'pro+'    => 3,
        'reponse' => 4,
        'eco'     => 5);

    /**
     * send 
     * 
     * @param mixed $txt : le texte du message
     * @param mixed $array_num_dest : tableau listant les numéros de mobile des destinataires au format national (0X XX XX XX XX)
     * @param string $type : pro / pro+ / reponse / eco
     * @param mixed $reference : une reference de notre choix qui sera renvoyee avec la reponse (par exemple l'id de l'annonce a laquelle on repond)
     * @access public
     * @return void
     */
    public function send($txt, $array_num_dest, $type = 'reponse', $reference = null)
    {
        $ns = $this->getModel('fonctions');
        $error_log = Clementine::$config['module_sms']['error_log'];
        // controle et transforme les paramètres d'appel
        if (!is_array($array_num_dest) || !count($array_num_dest)) {
            if ($error_log) {
                error_log('smsHelper->send : pas de destinataire');
            }
            return false;
        }
        // recupere la liste des numeros au format international
        $numeros = array();
        foreach ($array_num_dest as $num) {
            $num_valide = str_replace(' ', '', $num);
            $num_valide = preg_replace('/^0/', '33', $num_valide);
            $num_valide = (int) $num_valide;
            if (strlen($num_valide) == 11) {
                $numeros[] = $num_valide;
            }
        }
        $numeros = array_unique($numeros);
        if (!is_array($numeros) || !count($numeros)) {
            if ($error_log) {
                error_log('smsHelper->send : pas de destinataire au bon format');
            }
            return false;
        }
        $liste_numeros = implode(',', $numeros);
        // recuperation des parametres de config
        $gateway          = Clementine::$config['module_sms']['gateway'];
        $params_to_get   = array(
            'pseudo'           => Clementine::$config['module_sms']['pseudo'],
            'pass'             => Clementine::$config['module_sms']['pass'],
            'classe_msg'       => $this->classes_msg[$type],
            'message'          => rawurlencode($txt),
            'notification_url' => Clementine::$config['module_sms']['notification_url'],
            'refClient'        => $reference,
            // le SMS est composé de tronçons de 160 caractères maximum !
            'numero'           => $liste_numeros,
            'nbr_msg'          => count(str_split($txt, 160))
        );
        $params_to_post   = array(
        );
        // lance la requête
        $url = $gateway;
        foreach ($params_to_get as $key => $val) {
            $url = $ns->mod_param($url, $key, $val);
        }
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        // retourner le contenu de la requête au lieu de l'afficher
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        // ne pas retourner les headers http de la réponse
        curl_setopt($c, CURLOPT_HEADER, false);
        // pour une requete POST decommenter les lignes suivantes
        // curl_setopt($c, CURLOPT_POST, true);
        // curl_setopt($c, CURLOPT_POSTFIELDS, $params_to_post);
        $res = curl_exec($c);
        curl_close($c);
        if ($res === false) {
            $curl_error = curl_error($c);
            if ($error_log) {
                error_log('smsHelper->send : erreur curl' . $curl_error);
            }
            if (__DEBUGABLE__ && isset($_GET['debug'])) {
                trigger_error('Erreur curl : ' . $curl_error, E_USER_WARNING);
            }
        }
        $ret = explode('|', $res);
        if ($error_log) {
            $retour = 'non rempli';
            if (isset($ret[1])) {
                $retour = $ret[1];
            }
            error_log('smsHelper->send : envoye, retour : ' . $retour);
        }
        return $ret;
    }

}
?>
