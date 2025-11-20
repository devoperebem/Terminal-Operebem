<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Application;

class SystemMaintenanceService
{
    public function ensureCore(): void
    {
        $this->ensureAdminRefreshTable();
        $this->ensureUserRefreshTable();
        $this->ensureIndexes();
    }

    private function ensureAdminRefreshTable(): void
    {
        // Tabelas agora são gerenciadas via migration (PostgreSQL) ou já existem (MySQL)
        // Não tentar criar tabelas automaticamente para evitar conflitos de sintaxe
        return;
    }

    private function ensureUserRefreshTable(): void
    {
        // Tabelas agora são gerenciadas via migration (PostgreSQL) ou já existem (MySQL)
        // Não tentar criar tabelas automaticamente para evitar conflitos de sintaxe
        return;
    }

    private function tryIndex(string $sql): void
    {
        try { Database::query($sql); } catch (\Throwable $t) { /* ignore duplicate index errors */ }
    }

    private function ensureIndexes(): void
    {
        // Índices agora são gerenciados via migration (PostgreSQL) ou já existem (MySQL)
        // Não tentar criar índices automaticamente
        return;
    }
}
