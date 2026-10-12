<?php

namespace App\Domain\Members\Models;

use App\Domain\Payments\Models\MembershipPayment;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'first_name', 'last_name', 'birth_date', 'gender', 'phone', 'email',
    'region', 'commune', 'address', 'profession', 'photo_path', 'section',
    'referral_source', 'id_document_number', 'status', 'consent_at', 'joined_at',
])]
class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'consent_at' => 'datetime',
            'joined_at' => 'datetime',
            // Donnée sensible (CNI/passeport) — chiffrée au repos, CLAUDE.md §8.
            'id_document_number' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $member) {
            $member->membership_code ??= strtoupper(Str::random(10));
        });
    }

    /**
     * L'adhérent se résout par son code d'adhésion, jamais par sa clé primaire : une URL
     * `/admin/adherents/42` révélerait le nombre d'adhérents du parti et permettrait de
     * parcourir les fiches en incrémentant un nombre.
     *
     * Contrairement aux autres modèles, pas besoin du trait HasPublicId ici : `membership_code`
     * est déjà aléatoire (`strtoupper(Str::random(10))`), unique en base, et c'est l'identifiant
     * que l'adhérent voit sur sa carte — lui en ajouter un second n'apporterait rien.
     */
    public function getRouteKeyName(): string
    {
        return 'membership_code';
    }

    /**
     * Le modèle vit sous App\Domain\..., hors de la convention App\Models\ que Laravel
     * utilise pour deviner le nom de la factory — il faut l'indiquer explicitement.
     */
    protected static function newFactory(): Factory
    {
        return MemberFactory::new();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MembershipPayment::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Photo encodée en data URI pour la carte PDF/l'email — même approche que le QR code
     * déjà en data URI, évite toute dépendance à l'accès fichier de dompdf. `null` si
     * l'adhérent n'a pas fourni de photo (pas de portrait générique, CLAUDE.md §3.4).
     */
    public function photoDataUri(): ?string
    {
        if (! $this->photo_path || ! Storage::disk('public')->exists($this->photo_path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($this->photo_path);
        $mime = Storage::disk('public')->mimeType($this->photo_path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
