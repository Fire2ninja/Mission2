@extends('sommaireGestionnaire')

@section('contenu1')
    <div id="contenu">
        <h2>Suppression d’un visiteur</h2>
        @if (session('erreur'))
            <div class="erreur">{{ session('erreur') }}</div>
        @endif
        @if (session('message'))
            <div class="message">{{ session('message') }}</div>
        @endif

        @if ($lesVisiteurs === [])
            <p>Aucun visiteur à afficher.</p>
        @else
            <form method="post" action="{{ route('chemin_confirmerSuppressionVisiteur') }}">
                @csrf
                <div class="corpsForm">
                    <fieldset>
                        <legend>Sélectionner un visiteur</legend>
                        @foreach ($lesVisiteurs as $visiteur)
                            <p>
                                <input
                                    id="visiteur-{{ $visiteur['id'] }}"
                                    type="radio"
                                    name="idVisiteur"
                                    value="{{ $visiteur['id'] }}"
                                    required
                                >
                                <label for="visiteur-{{ $visiteur['id'] }}">
                                    {{ $visiteur['nom'] }} {{ $visiteur['prenom'] }}
                                </label>
                            </p>
                        @endforeach
                    </fieldset>
                </div>
                <div class="piedForm">
                    <p><input id="ok" type="submit" value="Sélectionner"></p>
                </div>
            </form>
        @endif
    </div>
@endsection
