<?php
declare(strict_types=1);

/**
 * User-administration reads/writes for the RRA's "Manage Users" screen.
 * User creation itself goes through the login library's UserRepository
 * (users + user_roles in one transaction); this class handles the gss88
 * profile row, role edits, and the activate/deactivate toggle.
 */
final class RappUserAdminRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listUsers(): array
    {
        $sql = "SELECT u.user_id, u.user_name AS email,
                       p.full_name, p.division_id, COALESCE(p.is_active, 1) AS is_active,
                       d.division_name,
                       GROUP_CONCAT(ro.role ORDER BY ro.role SEPARATOR ',') AS roles
                FROM users u
                LEFT JOIN gss88_user_profiles p ON p.user_id = u.user_id
                LEFT JOIN divisions_with_coordinators d ON d._id = p.division_id
                LEFT JOIN user_roles ur ON ur.user_id = u.user_id
                LEFT JOIN roles ro ON ro.role_id = ur.role_id
                GROUP BY u.user_id, u.user_name, p.full_name, p.division_id, p.is_active, d.division_name
                ORDER BY p.full_name IS NULL, p.full_name, u.user_name";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.user_id, u.user_name AS email,
                    p.full_name, p.division_id, COALESCE(p.is_active,1) AS is_active
             FROM users u
             LEFT JOIN gss88_user_profiles p ON p.user_id = u.user_id
             WHERE u.user_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $rstmt = $this->pdo->prepare(
            'SELECT ro.role FROM user_roles ur JOIN roles ro ON ro.role_id = ur.role_id WHERE ur.user_id = ?'
        );
        $rstmt->execute([$userId]);
        $row['roles'] = $rstmt->fetchAll(PDO::FETCH_COLUMN);
        return $row;
    }

    public function userIdByEmail(string $email): ?int
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM users WHERE user_name = ?');
        $stmt->execute([$email]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function upsertProfile(int $userId, string $fullName, ?int $divisionId, bool $isActive = true): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO gss88_user_profiles (user_id, full_name, division_id, is_active)
             VALUES (:u, :n, :d, :a)
             ON DUPLICATE KEY UPDATE full_name = VALUES(full_name),
                                     division_id = VALUES(division_id),
                                     is_active = VALUES(is_active)'
        );
        $stmt->execute([
            'u' => $userId,
            'n' => $fullName,
            'd' => $divisionId,
            'a' => $isActive ? 1 : 0,
        ]);
    }

    public function setActive(int $userId, bool $active): void
    {
        $this->pdo->prepare('UPDATE gss88_user_profiles SET is_active = ? WHERE user_id = ?')
            ->execute([$active ? 1 : 0, $userId]);
    }

    /** @param list<int> $roleIds */
    public function setRoles(int $userId, array $roleIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);
            $ins = $this->pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            foreach (array_unique($roleIds) as $rid) {
                $ins->execute([$userId, (int) $rid]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return list<array{id:int,label:string}> active users, for the alternate picker */
    public function activeUsersForPicker(): array
    {
        $sql = "SELECT u.user_id, u.user_name, p.full_name
                FROM users u
                LEFT JOIN gss88_user_profiles p ON p.user_id = u.user_id
                WHERE COALESCE(p.is_active,1) = 1
                ORDER BY p.full_name, u.user_name";
        $out = [];
        foreach ($this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = [
                'id' => (int) $r['user_id'],
                'label' => ($r['full_name'] ?: $r['user_name']) . ' <' . $r['user_name'] . '>',
            ];
        }
        return $out;
    }
}
