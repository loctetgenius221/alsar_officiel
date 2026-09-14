<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Champ honeypot (anti-bot en attendant Turnstile, cf. plan Phase 2) : un vrai
            // visiteur ne le remplit jamais, un bot le fait souvent.
            'website' => ['prohibited'],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['homme', 'femme'])],
            'phone' => ['required', 'string', 'max:30', $this->senegalesePhoneRule()],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'region' => ['required', Rule::in(config('adhesion.regions'))],
            'commune' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp'],
            'id_document_number' => ['nullable', 'string', 'regex:/^[A-Za-z0-9][A-Za-z0-9 -]{3,18}[A-Za-z0-9]$/'],
            'section' => ['required', Rule::in(['standard', 'jeunes', 'femmes'])],
            'referral_source' => ['nullable', 'string', 'max:150'],
            'consent' => ['accepted'],

            // La clé de palier seulement — jamais un montant (recalculé serveur).
            'tier' => ['nullable', Rule::in(array_keys(config('adhesion.tiers')))],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => "Vous devez accepter les statuts et la politique de confidentialité pour adhérer.",
            'email.unique' => 'Cette adresse email est déjà associée à une adhésion.',
            'id_document_number.regex' => 'Numéro de pièce invalide (5 à 20 caractères alphanumériques).',
        ];
    }

    /**
     * Numéro mobile sénégalais : 9 chiffres commençant par 7, indicatif +221 optionnel.
     * Ne fait jamais confiance au seul format côté client (CLAUDE.md §8) — même règle que
     * `adhesion-schemas.ts`, revalidée ici sur la valeur brute envoyée par le client.
     */
    private function senegalesePhoneRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $digits = preg_replace('/\D/', '', (string) $value);

            if (str_starts_with($digits, '221')) {
                $digits = substr($digits, 3);
            }

            if (! preg_match('/^7\d{8}$/', $digits)) {
                $fail('Numéro de téléphone sénégalais invalide (9 chiffres commençant par 7).');
            }
        };
    }
}
