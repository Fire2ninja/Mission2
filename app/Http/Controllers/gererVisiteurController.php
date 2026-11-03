<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PdoGsb;

class gererVisiteurController extends Controller
{
    public function sommaire()
    {
        if (!$this->gestionnaireConnecte()) {
            return redirect()->route('chemin_connexion');
        }

        return view('sommaireGestionnaire')
            ->with('gestionnaire', session('gestionnaire'));
    }

    public function listeVisiteurs()
    {
        if (!$this->gestionnaireConnecte()) {
            return redirect()->route('chemin_connexion');
        }

        return view('listeVisiteurs')
            ->with('gestionnaire', session('gestionnaire'))
            ->with('lesVisiteurs', PdoGsb::getLesVisiteurs());
    }

    public function confirmerSuppression(Request $request)
    {
        if (!$this->gestionnaireConnecte()) {
            return redirect()->route('chemin_connexion');
        }

        $idVisiteur = $request->validate([
            'idVisiteur' => ['required', 'string', 'max:4'],
        ])['idVisiteur'];
        $visiteur = PdoGsb::getVisiteur($idVisiteur);

        if ($visiteur === false) {
            return redirect()->route('chemin_listeVisiteurs')
                ->with('erreur', 'Le visiteur sélectionné n’existe pas.');
        }

        if (PdoGsb::aDesFichesDeFrais($idVisiteur)) {
            return redirect()->route('chemin_listeVisiteurs')
                ->with('erreur', 'Ce visiteur possède des fiches de frais et ne peut pas être supprimé.');
        }

        return view('confirmationSuppressionVisiteur')
            ->with('gestionnaire', session('gestionnaire'))
            ->with('visiteur', $visiteur);
    }

    public function supprimerVisiteur(string $id)
    {
        if (!$this->gestionnaireConnecte()) {
            return redirect()->route('chemin_connexion');
        }

        $visiteur = PdoGsb::getVisiteur($id);
        if ($visiteur === false) {
            return redirect()->route('chemin_listeVisiteurs')
                ->with('erreur', 'Le visiteur sélectionné n’existe pas.');
        }

        if (!PdoGsb::supprimerVisiteur($id)) {
            if (PdoGsb::getVisiteur($id) === false) {
                return redirect()->route('chemin_listeVisiteurs')
                    ->with('erreur', 'Le visiteur sélectionné n’existe plus.');
            }

            if (PdoGsb::aDesFichesDeFrais($id)) {
                return redirect()->route('chemin_listeVisiteurs')
                    ->with('erreur', 'Le visiteur possède des fiches de frais et ne peut pas être supprimé.');
            }

            return redirect()->route('chemin_listeVisiteurs')
                ->with('erreur', 'La suppression du visiteur a échoué. Veuillez réessayer.');
        }

        return redirect()->route('chemin_listeVisiteurs')
            ->with('message', 'Le visiteur a été supprimé.');
    }

    private function gestionnaireConnecte(): bool
    {
        return session('gestionnaire') !== null;
    }
}
