@extends('sommaireGestionnaire')

@section('contenu1')
    <div id="contenu">
        <h2>Confirmation de la suppression</h2>
        <p>
            Confirmez-vous la suppression de
            <strong>{{ $visiteur['nom'] }} {{ $visiteur['prenom'] }}</strong> ?
        </p>
        @if ($nbFiches > 0)
            <p><strong>Attention : ce visiteur possède {{ $nbFiches }} fiche(s) de frais.</strong></p>
            <p>Toutes ses données, y compris ses fiches de frais, seront archivées en JSON avant la suppression.</p>
        @else
            <p>Ce visiteur ne possède aucune fiche de frais.</p>
            <p>Ses informations seront archivées en JSON avant la suppression.</p>
        @endif
        <form method="post" action="{{ route('chemin_supprimerVisiteur', ['id' => $visiteur['id']]) }}">
            @csrf
            @method('DELETE')
            <div class="piedForm">
                <p>
                    <input id="ok" type="submit" value="Confirmer la suppression">
                    <a id="annuler" href="{{ route('chemin_menuGestionnaire') }}">Annuler</a>
                </p>
            </div>
        </form>
    </div>
@endsection
