<?php
declare(strict_types=1);

/**
 * gss88 RAPP capability model.
 *
 * Roles are additive: a user may hold several (e.g. a DC who is also on the
 * senior board), and the effective permission set is the union. Application
 * code asks this class about *capabilities*, never about role names.
 *
 * Capabilities:
 *   rapp.submit     file a Referee Abuse Prevention Program report
 *   rapp.view       read RAPP report text + listen to audio
 *   rapp.notify     receive the incident notification email
 *   rapp.retain     flag a report to survive the 72h purge / close it
 *   scores.view     view match scores
 *   gamecard.view   view game-card images
 *   matchdata.view  view staffing issues / match issues / sanctions
 *   users.manage    add / deactivate users, assign roles, set the alternate
 *
 * A capability is granted either at 'all' scope or 'division' scope. Only the
 * 'dc' role is division-scoped; every other grant is region-wide.
 */
final class AccessPolicy
{
    /** @var array<string, string[]> role => capabilities granted at 'all' scope */
    private const REGION_WIDE = [
        'ref' => ['rapp.submit'],
        'authorized_board' => ['scores.view', 'gamecard.view'],
        'senior_board' => [
            'rapp.view', 'rapp.notify', 'rapp.retain',
            'scores.view', 'gamecard.view', 'matchdata.view',
        ],
        'rra' => [
            'rapp.submit', 'rapp.view', 'rapp.notify', 'rapp.retain',
            'scores.view', 'gamecard.view', 'matchdata.view', 'users.manage',
        ],
        'system_manager' => ['users.manage'],
    ];

    /** @var array<string, string[]> role => capabilities granted only for the user's own division */
    private const DIVISION_SCOPED = [
        'dc' => ['scores.view', 'gamecard.view', 'matchdata.view'],
    ];

    /**
     * @param string[] $userRoles
     * @return 'all'|'division'|'none'
     */
    public function scopeFor(array $userRoles, string $capability): string
    {
        foreach ($userRoles as $role) {
            if (in_array($capability, self::REGION_WIDE[$role] ?? [], true)) {
                return 'all';
            }
        }
        foreach ($userRoles as $role) {
            if (in_array($capability, self::DIVISION_SCOPED[$role] ?? [], true)) {
                return 'division';
            }
        }
        return 'none';
    }

    /**
     * True if the user has the capability at any scope. Use for menu/route
     * gating; pair with canForDivision() before showing division data.
     *
     * @param string[] $userRoles
     */
    public function can(array $userRoles, string $capability): bool
    {
        return $this->scopeFor($userRoles, $capability) !== 'none';
    }

    /**
     * True if the user may exercise the capability against data belonging to
     * $targetDivisionId. Region-wide grants always pass; division-scoped grants
     * pass only when the target matches the user's own division.
     *
     * @param string[] $userRoles
     */
    public function canForDivision(
        array $userRoles,
        string $capability,
        ?int $targetDivisionId,
        ?int $userDivisionId
    ): bool {
        return match ($this->scopeFor($userRoles, $capability)) {
            'all' => true,
            'division' => $targetDivisionId !== null
                && $userDivisionId !== null
                && $targetDivisionId === $userDivisionId,
            default => false,
        };
    }

    /**
     * All capabilities the user holds and at what scope, for building navigation.
     *
     * @param string[] $userRoles
     * @return array<string, 'all'|'division'>
     */
    public function capabilities(array $userRoles): array
    {
        $allCaps = array_unique(array_merge(
            ...array_values(self::REGION_WIDE),
            ...array_values(self::DIVISION_SCOPED)
        ));

        $out = [];
        foreach ($allCaps as $cap) {
            $scope = $this->scopeFor($userRoles, $cap);
            if ($scope !== 'none') {
                $out[$cap] = $scope;
            }
        }
        return $out;
    }
}
