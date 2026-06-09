<?php
function ensureMemberColumns(PDO $pdo): void
{
    $databaseName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $statement = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = 'users'
           AND COLUMN_NAME IN ('is_member', 'kuet_batch', 'department', 'roll_no', 'member_joined_at')"
    );
    $statement->execute([$databaseName]);
    $existingColumns = array_column($statement->fetchAll(), 'COLUMN_NAME');

    $columns = [
        'is_member' => "ALTER TABLE users ADD COLUMN is_member TINYINT(1) NOT NULL DEFAULT 0 AFTER last_donation_date",
        'kuet_batch' => "ALTER TABLE users ADD COLUMN kuet_batch VARCHAR(20) NULL AFTER is_member",
        'department' => "ALTER TABLE users ADD COLUMN department VARCHAR(120) NULL AFTER kuet_batch",
        'roll_no' => "ALTER TABLE users ADD COLUMN roll_no VARCHAR(30) NULL AFTER department",
        'member_joined_at' => "ALTER TABLE users ADD COLUMN member_joined_at DATETIME NULL AFTER roll_no",
    ];

    foreach ($columns as $column => $sql) {
        if (!in_array($column, $existingColumns, true)) {
            $pdo->exec($sql);
        }
    }
}
