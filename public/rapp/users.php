<?php
declare(strict_types=1);

require_once __DIR__ . '/../../rapp/config/rapp-bootstrap.php';
require_once __DIR__ . '/../../rapp/src/rapp-layout.php';

$basePath = $rappConfig['base_path'];
$ctx = rapp_require('users.manage', $pdo, $authManager, $sessionManager, $accessPolicy, $basePath);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$allRoles = $userRepository->getAllRoles();          // [role_id => role_name]
$roleIdByName = array_flip($allRoles);
$divisions = $rappMatchData->divisions();
$error = null;
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!$sessionManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } elseif ($action === 'add') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $roleIds = array_map('intval', (array) ($_POST['roles'] ?? []));
        $divId = ($_POST['division_id'] ?? '') !== '' ? (int) $_POST['division_id'] : null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') {
            $error = 'A valid email and a name are required.';
        } elseif ($roleIds === []) {
            $error = 'Choose at least one role.';
        } else {
            try {
                $userRepository->addUser($email, bin2hex(random_bytes(24)), $roleIds);
                $newId = $rappUserAdmin->userIdByEmail($email);
                $rappUserAdmin->upsertProfile((int) $newId, $name, $divId, true);
                $auditRepository->logEvent('rapp_user_added', $ip, $ctx['email'], $email . ' roles=' . implode('|', array_map(fn ($r) => $allRoles[$r] ?? $r, $roleIds)));
                $notice = 'Added ' . $email . '.';
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'update') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $roleIds = array_map('intval', (array) ($_POST['roles'] ?? []));
        $divId = ($_POST['division_id'] ?? '') !== '' ? (int) $_POST['division_id'] : null;
        $isActive = isset($_POST['is_active']);

        if ($uid === $ctx['user_id'] && (!$isActive || !in_array((int) ($roleIdByName['rra'] ?? -1), $roleIds, true))) {
            $error = 'You cannot remove your own RRA access or deactivate yourself.';
        } elseif ($name === '' || $roleIds === []) {
            $error = 'A name and at least one role are required.';
        } else {
            $rappUserAdmin->setRoles($uid, $roleIds);
            $rappUserAdmin->upsertProfile($uid, $name, $divId, $isActive);
            $auditRepository->logEvent('rapp_user_updated', $ip, $ctx['email'], 'user #' . $uid);
            $notice = 'Updated.';
        }
    } elseif ($action === 'alt_add') {
        $altId = (int) ($_POST['alternate_user_id'] ?? 0);
        $starts = trim((string) ($_POST['starts_at'] ?? ''));
        $ends = trim((string) ($_POST['ends_at'] ?? ''));
        if ($altId <= 0 || $starts === '') {
            $error = 'Pick a person and a start date/time.';
        } else {
            $rappReports->addAlternate($altId, $ctx['user_id'], str_replace('T', ' ', $starts), $ends !== '' ? str_replace('T', ' ', $ends) : null);
            $auditRepository->logEvent('rapp_alternate_activated', $ip, $ctx['email'], 'alt user #' . $altId);
            $notice = 'Alternate activated.';
        }
    } elseif ($action === 'alt_remove') {
        $rappReports->deactivateAlternate((int) ($_POST['alt_id'] ?? 0));
        $auditRepository->logEvent('rapp_alternate_deactivated', $ip, $ctx['email'], 'alt row #' . (int) ($_POST['alt_id'] ?? 0));
        $notice = 'Alternate ended.';
    }
}

$csrf = $sessionManager->generateCsrfToken();
$editId = (int) ($_GET['edit'] ?? 0);
$editUser = $editId > 0 ? $rappUserAdmin->findUser($editId) : null;

rapp_layout_head('Manage users', $ctx['name'], $basePath);
?>
<div class="card">
    <p class="muted"><a href="<?= rapp_esc($basePath) ?>/index.php">&larr; Home</a></p>
    <h1>Manage users</h1>
    <?php if ($error): ?><div class="msg error"><?= rapp_esc($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="msg ok"><?= rapp_esc($notice) ?></div><?php endif; ?>

    <?php if ($editUser !== null): ?>
        <h2>Edit <?= rapp_esc($editUser['email']) ?></h2>
        <form method="post" action="users.php">
            <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrf) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?= (int) $editUser['user_id'] ?>">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" value="<?= rapp_esc($editUser['full_name']) ?>" required>
            <label>Roles</label>
            <?php foreach ($allRoles as $rid => $rname): ?>
                <label style="font-weight:normal"><input type="checkbox" name="roles[]" value="<?= (int) $rid ?>"
                    <?= in_array($rname, (array) $editUser['roles'], true) ? 'checked' : '' ?>> <?= rapp_esc($rname) ?></label>
            <?php endforeach; ?>
            <label for="division_id" style="margin-top:10px">Division (DC only)</label>
            <select name="division_id" id="division_id">
                <option value="">—</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= (int) $editUser['division_id'] === $d['id'] ? 'selected' : '' ?>><?= rapp_esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label style="font-weight:normal; margin-top:8px"><input type="checkbox" name="is_active" <?= (int) $editUser['is_active'] === 1 ? 'checked' : '' ?>> Active</label>
            <button class="btn block" type="submit">Save</button>
            <p class="muted"><a href="users.php">Cancel</a></p>
        </form>
    <?php else: ?>
        <table class="rapp">
            <tr><th>Name</th><th>Email</th><th>Roles</th><th>Division</th><th>Active</th><th></th></tr>
            <?php foreach ($rappUserAdmin->listUsers() as $u): ?>
                <tr>
                    <td><?= rapp_esc($u['full_name'] ?? '—') ?></td>
                    <td><?= rapp_esc($u['email']) ?></td>
                    <td><?= rapp_esc($u['roles'] ?? '—') ?></td>
                    <td><?= rapp_esc($u['division_name'] ?? '—') ?></td>
                    <td><?= (int) $u['is_active'] === 1 ? 'yes' : 'no' ?></td>
                    <td><a href="users.php?edit=<?= (int) $u['user_id'] ?>">edit</a></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2 style="margin-top:18px">Add a user</h2>
        <form method="post" action="users.php">
            <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrf) ?>">
            <input type="hidden" name="action" value="add">
            <label for="a_email">Email</label>
            <input type="email" id="a_email" name="email" required>
            <label for="a_name">Full name</label>
            <input type="text" id="a_name" name="full_name" required>
            <label>Roles</label>
            <?php foreach ($allRoles as $rid => $rname): ?>
                <label style="font-weight:normal"><input type="checkbox" name="roles[]" value="<?= (int) $rid ?>"> <?= rapp_esc($rname) ?></label>
            <?php endforeach; ?>
            <label for="a_div" style="margin-top:10px">Division (DC only)</label>
            <select name="division_id" id="a_div">
                <option value="">—</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"><?= rapp_esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn block" type="submit">Add user</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Alternate RRA</h2>
    <p class="muted">Activate an alternate only while you're away. They receive RAPP
    notifications and can view reports for the active period.</p>
    <table class="rapp">
        <tr><th>Person</th><th>From</th><th>Until</th><th></th></tr>
        <?php foreach ($rappReports->listActiveAlternates() as $a): ?>
            <tr>
                <td><?= rapp_esc($a['full_name'] ?: $a['email']) ?></td>
                <td><?= rapp_esc($a['starts_at']) ?></td>
                <td><?= rapp_esc($a['ends_at'] ?? 'until ended') ?></td>
                <td>
                    <form method="post" action="users.php" style="margin:0">
                        <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrf) ?>">
                        <input type="hidden" name="action" value="alt_remove">
                        <input type="hidden" name="alt_id" value="<?= (int) $a['_id'] ?>">
                        <button class="btn secondary" type="submit">End now</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <form method="post" action="users.php" style="margin-top:10px">
        <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrf) ?>">
        <input type="hidden" name="action" value="alt_add">
        <label for="alt_user">Person</label>
        <select name="alternate_user_id" id="alt_user" required>
            <option value="">—</option>
            <?php foreach ($rappUserAdmin->activeUsersForPicker() as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= rapp_esc($u['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="alt_start">From</label>
        <input type="datetime-local" name="starts_at" id="alt_start" required>
        <label for="alt_end">Until (optional)</label>
        <input type="datetime-local" name="ends_at" id="alt_end">
        <button class="btn block" type="submit">Activate alternate</button>
    </form>
</div>
<?php
rapp_layout_foot();
