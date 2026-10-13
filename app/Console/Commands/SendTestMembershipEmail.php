<?php

namespace App\Console\Commands;

use App\Domain\Members\Models\Member;
use App\Mail\MembershipConfirmed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envoie l'email de confirmation réel (template + carte PDF + QR) à une adresse de contrôle.
 *
 * À quoi ça sert : vérifier d'un seul geste, en production, que la chaîne complète fonctionne —
 * identifiants SMTP, template, pièce jointe, et domaine encodé dans le QR. Sans cette commande,
 * la seule façon de tester était de créer une fausse adhésion depuis le site public, ce qui
 * laisse un adhérent fictif en base.
 *
 * L'envoi est VOLONTAIREMENT immédiat (`send`, pas `defer`) : on veut voir l'erreur SMTP à
 * l'écran si elle survient, pas la découvrir plus tard dans un fichier de log.
 */
class SendTestMembershipEmail extends Command
{
    protected $signature = 'alsar:test-email
                            {email : Adresse qui doit recevoir le message}
                            {--member= : Code d\'un adhérent réel à utiliser (sinon, un adhérent fictif non enregistré)}';

    protected $description = "Envoie l'email de confirmation d'adhésion à une adresse, pour vérifier le template et la configuration SMTP";

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("« {$email} » n'est pas une adresse email valide.");

            return self::FAILURE;
        }

        $member = $this->resolveMember();

        if ($member === null) {
            return self::FAILURE;
        }

        $this->line('');
        $this->line("  Destinataire   : {$email}");
        $this->line("  Adhérent       : {$member->fullName()} ({$member->membership_code})");
        $this->line('  Expéditeur     : '.config('mail.from.address'));
        $this->line('  Transport      : '.config('mail.default'));
        // Le QR de la carte encode cette URL : c'est le contrôle le plus utile de la commande.
        $this->line('  QR de la carte : '.route('verifier', $member->membership_code));
        $this->line('');

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log : le message sera écrit dans storage/logs, PAS envoyé.');
        }

        try {
            Mail::to($email)->send(new MembershipConfirmed($member));
        } catch (Throwable $exception) {
            $this->error('Échec de l\'envoi : '.$exception->getMessage());
            $this->line('');
            $this->line('  Vérifier MAIL_HOST / MAIL_PORT / MAIL_USERNAME / MAIL_PASSWORD dans .env,');
            $this->line('  et que MAIL_FROM_ADDRESS est bien la boîte authentifiée.');

            return self::FAILURE;
        }

        $this->info('Message remis au serveur de mail.');

        return self::SUCCESS;
    }

    /**
     * Adhérent réel si un code est fourni, sinon un adhérent fictif **non enregistré** : on ne
     * veut pas qu'un test laisse une fausse adhésion dans la base de production.
     */
    private function resolveMember(): ?Member
    {
        $code = $this->option('member');

        if ($code === null) {
            return Member::make([
                'first_name' => 'Awa',
                'last_name' => 'Exemple',
                'email' => 'awa.exemple@alsar.sn',
                'phone' => '+221 77 000 00 00',
                'region' => 'Dakar',
                'commune' => 'Dakar',
                'section' => 'standard',
                'status' => 'active',
                'joined_at' => now(),
            ])->forceFill(['membership_code' => 'TEST'.Str::upper(Str::random(6))]);
        }

        $member = Member::where('membership_code', $code)->first();

        if (! $member) {
            $this->error("Aucun adhérent avec le code « {$code} ».");

            return null;
        }

        return $member;
    }
}
