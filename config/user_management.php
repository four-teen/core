<?php
declare(strict_types=1);

function user_management_role_options(): array
{
    return [
        'administrator' => 'Administrator',
        'staff' => 'Staff',
    ];
}

function user_management_role_label(string $role): string
{
    $options = user_management_role_options();
    return $options[$role] ?? 'Staff';
}

function user_management_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function user_management_normalize_role(string $role): string
{
    $role = strtolower(trim($role));
    $options = user_management_role_options();

    return array_key_exists($role, $options) ? $role : 'staff';
}

function ensure_user_management_table(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_user_management (
            user_management_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email_address VARCHAR(190) NOT NULL,
            full_name VARCHAR(150) NOT NULL DEFAULT '',
            account_role VARCHAR(50) NOT NULL DEFAULT 'administrator',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_tbl_user_management_email (email_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $initialized = true;
}

function user_management_count(PDO $pdo): int
{
    ensure_user_management_table($pdo);

    $statement = $pdo->query("SELECT COUNT(*) FROM tbl_user_management");
    return (int) $statement->fetchColumn();
}

function user_management_active_count(PDO $pdo): int
{
    ensure_user_management_table($pdo);

    $statement = $pdo->query("SELECT COUNT(*) FROM tbl_user_management WHERE is_active = 1");
    return (int) $statement->fetchColumn();
}

function user_management_find(PDO $pdo, int $userId): ?array
{
    ensure_user_management_table($pdo);

    $statement = $pdo->prepare(
        "SELECT
            user_management_id,
            email_address,
            full_name,
            account_role,
            is_active,
            created_at,
            updated_at
        FROM tbl_user_management
        WHERE user_management_id = :user_management_id
        LIMIT 1"
    );
    $statement->execute(['user_management_id' => $userId]);
    $user = $statement->fetch();

    return $user ?: null;
}

function user_management_find_by_email(PDO $pdo, string $email): ?array
{
    ensure_user_management_table($pdo);

    $statement = $pdo->prepare(
        "SELECT
            user_management_id,
            email_address,
            full_name,
            account_role,
            is_active,
            created_at,
            updated_at
        FROM tbl_user_management
        WHERE LOWER(email_address) = LOWER(:email)
        LIMIT 1"
    );
    $statement->execute(['email' => user_management_normalize_email($email)]);
    $user = $statement->fetch();

    return $user ?: null;
}

function user_management_list(PDO $pdo): array
{
    ensure_user_management_table($pdo);

    $statement = $pdo->query(
        "SELECT
            user_management_id,
            email_address,
            full_name,
            account_role,
            is_active,
            created_at,
            updated_at
        FROM tbl_user_management
        ORDER BY is_active DESC, account_role ASC, full_name ASC, email_address ASC"
    );

    return $statement->fetchAll();
}

function user_management_save(PDO $pdo, array $payload, ?int $userId = null): int
{
    ensure_user_management_table($pdo);

    $email = user_management_normalize_email((string) ($payload['email_address'] ?? ''));
    $fullName = trim((string) ($payload['full_name'] ?? ''));
    $accountRole = user_management_normalize_role((string) ($payload['account_role'] ?? 'staff'));
    $isActive = !empty($payload['is_active']) ? 1 : 0;

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    $existing = null;
    if ($userId !== null && $userId > 0) {
        $existing = user_management_find($pdo, $userId);

        if ($existing === null) {
            throw new RuntimeException('The selected user could not be found.');
        }

        if ((int) ($existing['is_active'] ?? 0) === 1 && $isActive !== 1 && user_management_active_count($pdo) <= 1) {
            throw new RuntimeException('At least one active user must remain in user management.');
        }
    }

    try {
        if ($existing !== null) {
            $statement = $pdo->prepare(
                "UPDATE tbl_user_management
                SET
                    email_address = :email_address,
                    full_name = :full_name,
                    account_role = :account_role,
                    is_active = :is_active
                WHERE user_management_id = :user_management_id"
            );
            $statement->execute([
                'email_address' => $email,
                'full_name' => $fullName,
                'account_role' => $accountRole,
                'is_active' => $isActive,
                'user_management_id' => $userId,
            ]);

            return $userId;
        }

        $statement = $pdo->prepare(
            "INSERT INTO tbl_user_management (
                email_address,
                full_name,
                account_role,
                is_active
            ) VALUES (
                :email_address,
                :full_name,
                :account_role,
                :is_active
            )"
        );
        $statement->execute([
            'email_address' => $email,
            'full_name' => $fullName,
            'account_role' => $accountRole,
            'is_active' => $isActive,
        ]);

        return (int) $pdo->lastInsertId();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            throw new RuntimeException('That email address is already listed in user management.', 0, $exception);
        }

        throw $exception;
    }
}

function user_management_delete(PDO $pdo, int $userId): void
{
    ensure_user_management_table($pdo);

    $existing = user_management_find($pdo, $userId);

    if ($existing === null) {
        throw new RuntimeException('The selected user could not be found.');
    }

    if ((int) ($existing['is_active'] ?? 0) === 1 && user_management_active_count($pdo) <= 1) {
        throw new RuntimeException('At least one active user must remain in user management.');
    }

    $statement = $pdo->prepare("DELETE FROM tbl_user_management WHERE user_management_id = :user_management_id");
    $statement->execute(['user_management_id' => $userId]);
}

function user_management_bootstrap_from_legacy_allowlist(PDO $pdo, array $profile): ?array
{
    ensure_user_management_table($pdo);

    if (user_management_count($pdo) > 0) {
        return null;
    }

    $email = user_management_normalize_email((string) ($profile['email'] ?? ''));

    if ($email === '' || !in_array($email, allowed_administrator_emails(), true)) {
        return null;
    }

    user_management_save($pdo, [
        'email_address' => $email,
        'full_name' => trim((string) ($profile['name'] ?? '')),
        'account_role' => 'administrator',
        'is_active' => 1,
    ]);

    return user_management_find_by_email($pdo, $email);
}
