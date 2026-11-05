<?php

/**
 * RBAC — Controle de Acesso Baseado em Papéis.
 * Centraliza as regras de autorização consultando as tabelas:
 * tbl_roles, tbl_permissions, tbl_role_permission e tbl_user_role.
 * Os papéis e permissões do usuário logado ficam em cache na sessão.
 */
class RBAC {
    private const SESS_ROLES = 'rbac_roles';
    private const SESS_PERM  = 'rbac_permissoes';

    /**
     * Carrega papéis e permissões do usuário para a sessão atual.
     */
    public static function carregarSessao(int $usuarioId): void {
        $roles = self::rolesDoUsuario($usuarioId);
        $permissoes = self::permissoesDoUsuario($usuarioId);

        $_SESSION[self::SESS_ROLES] = [];
        foreach ($roles as $role) {
            $_SESSION[self::SESS_ROLES][$role['slug']] = true;
        }

        $_SESSION[self::SESS_PERM] = [];
        foreach ($permissoes as $perm) {
            $_SESSION[self::SESS_PERM][$perm['slug']] = true;
        }
    }

    /**
     * Verifica se o usuário logado possui determinado papel (role).
     */
    public static function hasRole(string $slug): bool {
        $roles = $_SESSION[self::SESS_ROLES] ?? null;
        if ($roles === null) {
            self::carregarSessao((int) $_SESSION['usuario_id']);
            $roles = $_SESSION[self::SESS_ROLES] ?? [];
        }
        return isset($roles[$slug]) && $roles[$slug];
    }

    /**
     * Verifica se o usuário logado possui determinada permissão.
     */
    public static function can(string $slug): bool {
        $perm = $_SESSION[self::SESS_PERM] ?? null;
        if ($perm === null) {
            self::carregarSessao((int) $_SESSION['usuario_id']);
            $perm = $_SESSION[self::SESS_PERM] ?? [];
        }
        return isset($perm[$slug]) && $perm[$slug];
    }

    /**
     * Lista os papéis de um usuário (papéis e permissões vêm do banco).
     */
    public static function rolesDoUsuario(int $usuarioId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT r.slug, r.nome
               FROM tbl_user_role ur
               JOIN tbl_roles r ON r.id = ur.fk_role
              WHERE ur.fk_usuario = :id
              ORDER BY r.slug"
        );
        $stmt->execute(['id' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lista as permissões (distintas) de um usuário via seus papéis.
     */
    public static function permissoesDoUsuario(int $usuarioId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT DISTINCT p.slug, p.modulo
               FROM tbl_user_role ur
               JOIN tbl_role_permission rp ON rp.fk_role = ur.fk_role
               JOIN tbl_permissions p ON p.id = rp.fk_permission
              WHERE ur.fk_usuario = :id
              ORDER BY p.slug"
        );
        $stmt->execute(['id' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Atribui um papel a um usuário (idempotente).
     */
    public static function atribuirRole(int $usuarioId, string $slug): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM tbl_roles WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $roleId = $stmt->fetchColumn();
        if (!$roleId) {
            return false;
        }
        $ins = $db->prepare(
            "INSERT IGNORE INTO tbl_user_role (fk_usuario, fk_role) VALUES (:u, :r)"
        );
        return $ins->execute(['u' => $usuarioId, 'r' => (int) $roleId]);
    }

    /**
     * Remove todos os papéis de um usuário (útil ao desativar/reclassificar).
     */
    public static function removerRoles(int $usuarioId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM tbl_user_role WHERE fk_usuario = :id");
        return $stmt->execute(['id' => $usuarioId]);
    }

    /**
     * Redefine os papéis de um usuário conforme seu perfil atual.
     * Mantém tbl_user_role sempre espelhando a role primária do usuário.
     */
    public static function redefinirRole(int $usuarioId, string $perfil): bool {
        self::removerRoles($usuarioId);
        return self::atribuirRole($usuarioId, $perfil);
    }
}