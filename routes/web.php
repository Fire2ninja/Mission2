<?php

use Illuminate\Support\Facades\Route;
// Chemin des contrôleurs
use App\Http\Controllers\connexionController;
use App\Http\Controllers\etatFraisController;
use App\Http\Controllers\gererFraisController;
use App\Http\Controllers\gererVisiteurController;

// Création des groupes de routes
Route::controller(connexionController::class)->group(function () {
    Route::get('/', 'connecter')->name('chemin_connexion');
    Route::post('/', 'valider')->name('chemin_valider');
    Route::get('/deconnexion', 'deconnecter')->name('chemin_deconnexion');
});

Route::controller(etatFraisController::class)->group(function () {
    Route::get('/selectionMois', 'selectionnerMois')->name('chemin_selectionMois');
    Route::post('/listeFrais', 'voirFrais')->name('chemin_listeFrais');
});

Route::controller(gererFraisController::class)->group(function () {
    Route::get('/gererFrais', 'saisirFrais')->name('chemin_gestionFrais');
    Route::post('/sauvegarderFrais', 'sauvegarderFrais')->name('chemin_sauvegardeFrais');
});

Route::controller(gererVisiteurController::class)->group(function () {
    Route::get('/sommaireGestionnaire', 'sommaire')->name('chemin_menuGestionnaire');
    Route::get('/gererVisiteur', 'listeVisiteurs')->name('chemin_listeVisiteurs');
    Route::post('/gererVisiteur/confirmer', 'confirmerSuppression')->name('chemin_confirmerSuppressionVisiteur');
    Route::delete('/gererVisiteur/{id}', 'supprimerVisiteur')->name('chemin_supprimerVisiteur');
});