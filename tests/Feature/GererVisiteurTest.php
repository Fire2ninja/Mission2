<?php

namespace Tests\Feature;

use App\Facades\PdoGsb;
use Tests\TestCase;

class GererVisiteurTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
        PdoGsb::swap(\Mockery::mock());
    }

    public function test_a_visitor_cannot_open_visitor_management(): void
    {
        $this->get(route('chemin_listeVisiteurs'))
            ->assertRedirect(route('chemin_connexion'));
    }

    public function test_a_gestionnaire_can_see_the_visitors_list(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getLesVisiteurs')->once()->andReturn([
            ['id' => 'a131', 'nom' => 'Villechalane', 'prenom' => 'Louis'],
        ]);

        $this->get(route('chemin_listeVisiteurs'))
            ->assertOk()
            ->assertSee('Villechalane Louis');
    }

    public function test_a_gestionnaire_can_sign_in(): void
    {
        PdoGsb::shouldReceive('getInfosVisiteur')->once()->andReturn(false);
        PdoGsb::shouldReceive('getInfosGestionnaire')->once()->with('gestion', '7K5t3')->andReturn([
            'id' => 'g001',
            'nom' => 'Gestion',
            'prenom' => 'Service',
        ]);

        $this->post(route('chemin_valider'), ['login' => 'gestion', 'mdp' => '7K5t3'])
            ->assertOk()
            ->assertViewIs('sommaireGestionnaire')
            ->assertSessionHas('gestionnaire.id', 'g001')
            ->assertSessionMissing('visiteur');
    }

    public function test_a_visitor_with_expense_reports_cannot_be_deleted(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getVisiteur')->once()->with('a131')->andReturn([
            'id' => 'a131',
            'nom' => 'Villechalane',
            'prenom' => 'Louis',
        ]);
        PdoGsb::shouldReceive('aDesFichesDeFrais')->once()->with('a131')->andReturn(true);

        $this->post(route('chemin_confirmerSuppressionVisiteur'), ['idVisiteur' => 'a131'])
            ->assertRedirect(route('chemin_listeVisiteurs'))
            ->assertSessionHas('erreur');
    }

    public function test_a_visitor_without_expense_reports_gets_a_confirmation_page(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getVisiteur')->once()->with('a131')->andReturn([
            'id' => 'a131',
            'nom' => 'Villechalane',
            'prenom' => 'Louis',
        ]);
        PdoGsb::shouldReceive('aDesFichesDeFrais')->once()->with('a131')->andReturn(false);

        $this->post(route('chemin_confirmerSuppressionVisiteur'), ['idVisiteur' => 'a131'])
            ->assertOk()
            ->assertSee('Confirmation de la suppression')
            ->assertSee('Villechalane Louis')
            ->assertSee(route('chemin_menuGestionnaire'));
    }

    public function test_a_gestionnaire_can_delete_a_visitor_without_expense_reports(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getVisiteur')->once()->with('a131')->andReturn([
            'id' => 'a131',
            'nom' => 'Villechalane',
            'prenom' => 'Louis',
        ]);
        PdoGsb::shouldReceive('supprimerVisiteur')->once()->with('a131')->andReturn(true);

        $this->delete(route('chemin_supprimerVisiteur', ['id' => 'a131']))
            ->assertRedirect(route('chemin_listeVisiteurs'))
            ->assertSessionHas('message', 'Le visiteur a été supprimé.');
    }

    private function actingAsGestionnaire(): void
    {
        $this->withSession([
            'gestionnaire' => ['id' => 'g001', 'nom' => 'Gestion', 'prenom' => 'Service'],
        ]);
    }
}
