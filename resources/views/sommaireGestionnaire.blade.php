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
                     {{-- À remplacer par route('chemin_supprimerVisiteur') quand la route de Mathys existera --}}
                     <a href="#" title="Supprimer un visiteur">Supprimer un visiteur</a>
                  </li>
               <li class="smenu">
                <a href="{{ route('chemin_deconnexion') }}" title="Se déconnecter">Déconnexion</a>
                  </li>
                </ul>
        </div>
    @endsection
