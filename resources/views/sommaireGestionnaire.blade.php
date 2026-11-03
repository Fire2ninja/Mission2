@extends ('modeles/visiteur')
    @section('menu')
            <!-- Division pour le sommaire du gestionnaire -->
        <div id="menuGauche">
            <div id="infosUtil">

             </div>
               <ul id="menuList">
                   <li >
                    <strong>Bonjour {{ $gestionnaire['nom'] . ' ' . $gestionnaire['prenom'] }}</strong>
                   </li>
                  <li class="smenu">
                     <a href="{{ route('chemin_listeVisiteurs') }}" title="Gérer les visiteurs">Gérer les visiteurs</a>
                  </li>
               <li class="smenu">
                <a href="{{ route('chemin_deconnexion') }}" title="Se déconnecter">Déconnexion</a>
                  </li>
                </ul>
        </div>
    @endsection

    @if (!empty($afficherAccueilGestionnaire))
        @section('contenu1')
            <div id="contenu">
                <h2>Menu principal du gestionnaire</h2>
            </div>
        @endsection
    @endif
