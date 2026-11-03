<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PdoGsb;
use RuntimeException;
use Throwable;

class gererVisiteurController extends Controller
{
    public function sommaire()
    {
        if (!$this->gestionnaireConnecte()) {
            return redirect()->route('chemin_connexion');
        }

        return view('sommaireGestionnaire')
            ->with('gestionnaire', session('gestionnaire'))
            ->with('afficherAccueilGestionnaire', true);
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

        return view('confirmationSuppressionVisiteur')
            ->with('gestionnaire', session('gestionnaire'))
            ->with('visiteur', $visiteur);
    }

    public function supprimerVisiteur(string $id)
    {
        if (!$this->gestionnaireConnecte()) {
            return redirect()->route('chemin_connexion');
        }

        $cheminArchive = null;
        try {
            $cheminArchive = PdoGsb::archiverEtSupprimerVisiteur(
                $id,
                function (array $donnees) use (&$cheminArchive): string {
                    $cheminArchive = sprintf(
                        'archives/visiteurs/%s_%s.json',
                        now()->format('Ymd_His'),
                        Str::uuid()
                    );
                    $contenu = json_encode(
                        $donnees,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                    );

                    if (!Storage::disk('local')->put($cheminArchive, $contenu)) {
                        throw new RuntimeException('Impossible d’écrire le fichier d’archive.');
                    }

                    return $cheminArchive;
                }
            );
        } catch (Throwable $exception) {
            if ($cheminArchive !== null) {
                Storage::disk('local')->delete($cheminArchive);
            }
            Log::error('Échec de l’archivage ou de la suppression d’un visiteur.', [
                'id_visiteur' => $id,
                'exception' => $exception,
            ]);

            return redirect()->route('chemin_listeVisiteurs')
                ->with('erreur', 'L’archivage ou la suppression a échoué. Aucune suppression n’a été conservée.');
        }

        if ($cheminArchive === null) {
            return redirect()->route('chemin_listeVisiteurs')
                ->with('erreur', 'Le visiteur sélectionné n’existe plus.');
        }

        return redirect()->route('chemin_listeVisiteurs')
            ->with('message', 'Le visiteur a été archivé puis supprimé. Archive : ' . $cheminArchive);
    }

    private function gestionnaireConnecte(): bool
    {
        return session('gestionnaire') !== null;
    }
}
