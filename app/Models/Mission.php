<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'hazard_weightings' => 'array',
        ];
    }

    public function crewProfiles(): HasMany
    {
        return $this->hasMany(CrewProfile::class);
    }

    /**
     * Get hazard weightings based on mission profile.
     * Default weightings reflect mission-specific risk distributions.
     */
    public function hazardWeights(): array
    {
        if ($this->hazard_weightings) {
            return $this->hazard_weightings;
        }

        return self::weightsForProfile($this->mission_profile ?? 'LEO');
    }

    /** Illustrative scenario multipliers (team assumptions), not NASA-published values. */
    public static function weightsForProfile(string $profile): array
    {
        return match ($profile) {
            'LEO' => [
                'radiation' => 1.0,
                'isolation' => 1.0,
                'distance' => 0.3,
                'gravity' => 1.0,
                'environment' => 1.0,
            ],
            'Moon' => [
                'radiation' => 1.2,
                'isolation' => 1.3,
                'distance' => 1.5,
                'gravity' => 0.8,
                'environment' => 1.1,
            ],
            'Mars' => [
                'radiation' => 1.5,
                'isolation' => 1.8,
                'distance' => 2.0,
                'gravity' => 0.5,
                'environment' => 1.2,
            ],
            default => [
                'radiation' => 1.0,
                'isolation' => 1.0,
                'distance' => 1.0,
                'gravity' => 1.0,
                'environment' => 1.0,
            ],
        };
    }
}
