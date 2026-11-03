<?php

namespace Tests\Feature;

use App\Facades\PdoGsb;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GererVisiteurTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
        PdoGsb::swap(\Mockery::mock());
        Storage::fake('local');
    }

    public function test_a_non_gestionnaire_cannot_access_visitor_management_or_delete(): void
    {
        PdoGsb::shouldNotReceive('archiverEtSupprimerVisiteur');

        $this->get(route('chemin_listeVisiteurs'))
            ->assertRedirect(route('chemin_connexion'));

        $this->delete(route('chemin_supprimerVisiteur', ['id' => 'a131']))
            ->assertRedirect(route('chemin_connexion'));
    }

    public function test_a_gestionnaire_can_open_the_visitor_list(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getLesVisiteurs')->once()->andReturn([
            ['id' => 'a131', 'nom' => 'Villechalane', 'prenom' => 'Louis'],
        ]);

        $this->get(route('chemin_listeVisiteurs'))
            ->assertOk()
            ->assertSee('Villechalane Louis');
    }

    public function test_a_gestionnaire_can_sign_in_without_retaining_a_visitor_session(): void
    {
        $this->withSession(['visiteur' => ['id' => 'old1']]);
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

    public function test_a_visitor_with_expense_reports_can_be_selected_for_archiving(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getVisiteur')->once()->with('a131')->andReturn([
            'id' => 'a131',
            'nom' => 'Villechalane',
            'prenom' => 'Louis',
        ]);

        $this->post(route('chemin_confirmerSuppressionVisiteur'), ['idVisiteur' => 'a131'])
            ->assertOk()
            ->assertViewIs('confirmationSuppressionVisiteur')
            ->assertSee('y compris ses fiches de frais');
    }

    public function test_a_visitor_without_expense_reports_can_be_selected_for_archiving(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getVisiteur')->once()->with('b16')->andReturn([
            'id' => 'b16',
            'nom' => 'Bioret',
            'prenom' => 'Luc',
        ]);

        $this->post(route('chemin_confirmerSuppressionVisiteur'), ['idVisiteur' => 'b16'])
            ->assertOk()
            ->assertSee('Bioret Luc');
    }

    public function test_deletion_archives_all_visitor_and_expense_data_before_deleting(): void
    {
        $this->actingAsGestionnaire();
        $archiveData = $this->archiveDataWithExpenseReport();
        PdoGsb::shouldReceive('archiverEtSupprimerVisiteur')
            ->once()
            ->with('a131', \Mockery::type('callable'))
            ->andReturnUsing(fn (string $id, callable $archiver): string => $archiver($archiveData));

        $this->delete(route('chemin_supprimerVisiteur', ['id' => 'a131']))
            ->assertRedirect(route('chemin_listeVisiteurs'))
            ->assertSessionHas('message', fn (string $message) => str_contains($message, 'archivé puis supprimé'));

        $archives = Storage::disk('local')->files('archives/visiteurs');
        $this->assertCount(1, $archives);
        $this->assertSame(
            $archiveData,
            json_decode(Storage::disk('local')->get($archives[0]), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    public function test_deletion_archives_and_removes_a_visitor_without_expense_reports(): void
    {
        $this->actingAsGestionnaire();
        $archiveData = [
            'format_version' => 1,
            'exported_at' => '2026-11-03T14:00:00+01:00',
            'visitor' => ['id' => 'b16', 'nom' => 'Bioret', 'prenom' => 'Luc'],
            'expense_reports' => [],
        ];
        PdoGsb::shouldReceive('archiverEtSupprimerVisiteur')
            ->once()
            ->andReturnUsing(fn (string $id, callable $archiver): string => $archiver($archiveData));

        $this->delete(route('chemin_supprimerVisiteur', ['id' => 'b16']))
            ->assertRedirect(route('chemin_listeVisiteurs'))
            ->assertSessionHas('message');

        $archives = Storage::disk('local')->files('archives/visiteurs');
        $this->assertCount(1, $archives);
        $this->assertSame(
            $archiveData,
            json_decode(Storage::disk('local')->get($archives[0]), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    public function test_cancelling_confirmation_returns_to_menu_without_archiving_or_deleting(): void
    {
        $this->actingAsGestionnaire();
        PdoGsb::shouldReceive('getVisiteur')->once()->with('a131')->andReturn([
            'id' => 'a131',
            'nom' => 'Villechalane',
            'prenom' => 'Louis',
        ]);
        PdoGsb::shouldNotReceive('archiverEtSupprimerVisiteur');

        $this->post(route('chemin_confirmerSuppressionVisiteur'), ['idVisiteur' => 'a131'])
            ->assertOk()
            ->assertSee(route('chemin_menuGestionnaire'));
        $this->get(route('chemin_menuGestionnaire'))
            ->assertOk()
            ->assertSee('Menu principal du gestionnaire');
        Storage::disk('local')->assertDirectoryEmpty('archives/visiteurs');
    }

    public function test_archive_is_removed_when_transactional_deletion_fails(): void
    {
        $this->actingAsGestionnaire();
        $archiveData = [
            'format_version' => 1,
            'exported_at' => '2026-11-03T14:00:00+01:00',
            'visitor' => ['id' => 'b16', 'nom' => 'Bioret', 'prenom' => 'Luc'],
            'expense_reports' => [],
        ];
        PdoGsb::shouldReceive('archiverEtSupprimerVisiteur')
            ->once()
            ->andReturnUsing(function (string $id, callable $archiver) use ($archiveData): never {
                $archiver($archiveData);
                throw new \RuntimeException('Simulated database failure');
            });

        $this->delete(route('chemin_supprimerVisiteur', ['id' => 'b16']))
            ->assertRedirect(route('chemin_listeVisiteurs'))
            ->assertSessionHas('erreur');
        Storage::disk('local')->assertDirectoryEmpty('archives/visiteurs');
    }

    private function archiveDataWithExpenseReport(): array
    {
        return [
            'format_version' => 1,
            'exported_at' => '2026-11-03T14:00:00+01:00',
            'visitor' => [
                'id' => 'a131',
                'nom' => 'Villechalane',
                'prenom' => 'Louis',
                'login' => 'aribiA',
                'mdp' => 'aaaa',
                'adresse' => '8 rue des Charmes',
                'cp' => '46000',
                'ville' => 'Cahors',
                'dateEmbauche' => '2015-12-21',
            ],
            'expense_reports' => [
                [
                    'idVisiteur' => 'a131',
                    'mois' => '202601',
                    'nbJustificatifs' => 2,
                    'montantValide' => '85.50',
                    'dateModif' => '2026-02-01',
                    'idEtat' => 'RB',
                    'flat_fee_lines' => [
                        [
                            'idVisiteur' => 'a131',
                            'mois' => '202601',
                            'idFraisForfait' => 'KM',
                            'quantite' => 125,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function actingAsGestionnaire(): void
    {
        $this->withSession([
            'gestionnaire' => ['id' => 'g001', 'nom' => 'Gestion', 'prenom' => 'Service'],
        ]);
    }
}
