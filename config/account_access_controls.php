<?php
declare(strict_types=1);

function account_access_control_options(): array
{
    return [
        'students' => 'Student Accounts',
        'faculty' => 'Faculty Accounts',
    ];
}

function account_access_control_default_rows(): array
{
    $rows = [];

    foreach (account_access_control_options() as $key => $label) {
        $rows[$key] = [
            'control_key' => $key,
            'label' => $label,
            'is_locked' => 0,
            'updated_by_user_management_id' => null,
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    return $rows;
}

function account_access_control_normalize_key(string $key): string
{
    $key = strtolower(trim($key));
    $options = account_access_control_options();

    if (!array_key_exists($key, $options)) {
        throw new RuntimeException('Please select a valid account access control.');
    }

    return $key;
}

function account_access_control_label(string $key): string
{
    $key = account_access_control_normalize_key($key);
    $options = account_access_control_options();

    return $options[$key];
}

function ensure_account_access_controls_table(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_account_access_controls (
            control_key VARCHAR(50) NOT NULL PRIMARY KEY,
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            updated_by_user_management_id INT UNSIGNED NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $statement = $pdo->prepare(
        "INSERT IGNORE INTO tbl_account_access_controls (
            control_key,
            is_locked
        ) VALUES (
            :control_key,
            0
        )"
    );

    foreach (array_keys(account_access_control_options()) as $controlKey) {
        $statement->execute(['control_key' => $controlKey]);
    }

    $initialized = true;
}

function account_access_controls(PDO $pdo): array
{
    ensure_account_access_controls_table($pdo);

    $controls = account_access_control_default_rows();
    $statement = $pdo->query(
        "SELECT
            control_key,
            is_locked,
            updated_by_user_management_id,
            created_at,
            updated_at
        FROM tbl_account_access_controls"
    );

    foreach ($statement->fetchAll() as $row) {
        $controlKey = strtolower(trim((string) ($row['control_key'] ?? '')));

        if (!array_key_exists($controlKey, $controls)) {
            continue;
        }

        $controls[$controlKey] = array_merge($controls[$controlKey], [
            'is_locked' => (int) ($row['is_locked'] ?? 0),
            'updated_by_user_management_id' => $row['updated_by_user_management_id'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ]);
    }

    return $controls;
}

function account_access_control_is_locked(PDO $pdo, string $key): bool
{
    $key = account_access_control_normalize_key($key);
    ensure_account_access_controls_table($pdo);

    $statement = $pdo->prepare(
        "SELECT is_locked
        FROM tbl_account_access_controls
        WHERE control_key = :control_key
        LIMIT 1"
    );
    $statement->execute(['control_key' => $key]);

    return (int) ($statement->fetchColumn() ?: 0) === 1;
}

function account_access_control_set(PDO $pdo, string $key, bool $isLocked, ?int $updatedByUserId): void
{
    $key = account_access_control_normalize_key($key);
    ensure_account_access_controls_table($pdo);

    $statement = $pdo->prepare(
        "UPDATE tbl_account_access_controls
        SET
            is_locked = :is_locked,
            updated_by_user_management_id = :updated_by_user_management_id,
            updated_at = NOW()
        WHERE control_key = :control_key"
    );
    $statement->execute([
        'control_key' => $key,
        'is_locked' => $isLocked ? 1 : 0,
        'updated_by_user_management_id' => $updatedByUserId !== null && $updatedByUserId > 0 ? $updatedByUserId : null,
    ]);
}
