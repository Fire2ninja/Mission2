<?php

namespace App\Http\Controllers;
use PdoGsb;
use Illuminate\Http\Request;

class connexionController extends Controller
{
    function connecter(){
        
        return view('connexion')->with('erreurs',null);
    } 
function valider(Request $request){
    $login = $request['login'];
    $mdp = $request['mdp'];

    $visiteur = PdoGsb::getInfosVisiteur($login, $mdp);
    if(is_array($visiteur)){
        session(['visiteur' => $visiteur]);
        return view('sommaire')->with('visiteur', session('visiteur'));
    }

    $gestionnaire = PdoGsb::getInfosGestionnaire($login, $mdp);
    if(is_array($gestionnaire)){
        session(['gestionnaire' => $gestionnaire]);
        return view('sommaireGestionnaire')->with('gestionnaire', session('gestionnaire'));
    }

    $erreurs[] = "Login ou mot de passe incorrect(s)";
    return view('connexion')->with('erreurs', $erreurs);
}

function deconnecter(){
    session(['visiteur' => null, 'gestionnaire' => null]);
    return redirect()->route('chemin_connexion');
}
}