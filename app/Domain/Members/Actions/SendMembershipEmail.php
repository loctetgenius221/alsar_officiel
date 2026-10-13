<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use App\Mail\MembershipConfirmed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Point d'envoi unique de l'email de confirmation (carte + QR en pièce jointe).
 *
 * POURQUOI CETTE CLASSE EXISTE — l'email partait auparavant par `Mail::to(...)->queue(...)`
 * avec `QUEUE_CONNECTION=database`. Cet appel n'envoie rien : il écrit une ligne dans la table
 * `jobs`, et le message n'est expédié que lorsqu'un worker consomme la file. Sur un hébergement
 * mutualisé sans cron, ce worker n'existe pas : aucune erreur, aucun log, et les emails
 * s'empilent indéfiniment sans que personne ne s'en aperçoive.
 *
 * `defer()` règle le problème sans rien demander au serveur : le message part APRÈS que la
 * réponse HTTP a été envoyée au navigateur (middleware `InvokeDeferredCallbacks`, actif par
 * défaut). L'adhérent n'attend donc ni la génération du PDF ni la connexion SMTP, et aucun
 * cron n'est nécessaire.
 *
 * Le `try/catch` n'est pas une précaution de principe : sans lui, une panne du serveur de mail
 * deviendrait une erreur 500 sur le parcours d'adhésion. Une inscription ne doit jamais échouer
 * parce que l'email de bienvenue n'est pas parti — on journalise, et la carte reste renvoyable à
 * la main depuis la fiche adhérent du back-office.
 *
 * Contrepartie assumée : pas de réessai automatique (c'est ce qu'apporterait une vraie file avec
 * worker). Pour ce volume, le bouton « Renvoyer la carte par email » est le rattrapage.
 */
class SendMembershipEmail
{
    public function handle(Member $member, ?MembershipPayment $payment = null): void
    {
        // `defer()` n'exécute pas le rappel si la requête s'est soldée par une exception :
        // inutile d'annoncer une adhésion qui n'a pas abouti.
        defer(function () use ($member, $payment) {
            try {
                Mail::to($member->email)->send(new MembershipConfirmed($member, $payment));
            } catch (Throwable $exception) {
                Log::error("Email de confirmation d'adhésion non envoyé", [
                    'membership_code' => $member->membership_code,
                    'email' => $member->email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        });
    }
}
