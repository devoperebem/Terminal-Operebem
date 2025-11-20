<?php

namespace App\Controllers;
use App\Core\Database;

class ProfileController extends BaseController
{
    public function index(): void
    {
        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->redirect('/login');
        }

        // Compute avatar URL if exists
        $root = dirname(__DIR__, 2); // novo_public_html
        $docroot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
        $publicPath = $docroot !== '' ? $docroot : ($root . DIRECTORY_SEPARATOR . 'public');
        $uploadsDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
        $avatarPath = '';
        $candidates = [
            $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.png',
            $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.jpg',
            $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.jpeg',
            $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.webp',
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) { $avatarPath = $c; break; }
        }
        $avatarUrl = '';
        if ($avatarPath) {
            $rel = '/uploads/avatars/' . basename($avatarPath);
            $v = @filemtime($avatarPath) ?: time();
            $avatarUrl = $rel . '?v=' . $v;
        }

        // Obter timezones suportados
        $timezones = \App\Services\TimezoneService::getSupportedTimezones();

        $this->view('profile/index', [
            'user' => $user,
            'avatar_url' => $avatarUrl,
            'timezones' => $timezones,
        ]);
    }

    public function updatePreferences(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
        }

        // Garantir que valores booleanos sejam enviados corretamente (PostgreSQL é rigoroso com tipos)
        $timezone = $_POST['timezone'] ?? 'America/Sao_Paulo';
        
        // Validar timezone
        if (!\App\Services\TimezoneService::isValidTimezone($timezone)) {
            $timezone = 'America/Sao_Paulo';
        }
        
        $preferences = [
            'theme' => $_POST['theme'] ?? null,
            'timezone' => $timezone,
            'media_card' => !empty($_POST['media_card']) && ($_POST['media_card'] === '1' || $_POST['media_card'] === 'on' || $_POST['media_card'] === 'true'),
            'advanced_snapshot' => !empty($_POST['advanced_snapshot']) && ($_POST['advanced_snapshot'] === '1' || $_POST['advanced_snapshot'] === 'on' || $_POST['advanced_snapshot'] === 'true')
        ];

        $success = $this->authService->updateUserPreferences($user['id'], $preferences);

        if ($success) {
            // Atualizar timezone na sessão
            $_SESSION['user_timezone'] = $timezone;
            
            $this->json([
                'success' => true,
                'message' => 'Preferências atualizadas com sucesso!'
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Erro ao atualizar preferências'
            ], 500);
        }
    }

    public function changePassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_new_password'] ?? '';

        // Validações
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->json(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        }

        if ($newPassword !== $confirmPassword) {
            $this->json(['success' => false, 'message' => 'As senhas não coincidem']);
        }

        if (strlen($newPassword) < 8) {
            $this->json(['success' => false, 'message' => 'A nova senha deve ter pelo menos 8 caracteres']);
        }

        try {
            // Verificar senha atual
            $userData = Database::fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
            
            if (!$userData || !password_verify($currentPassword, $userData['password'])) {
                $this->json(['success' => false, 'message' => 'Senha atual incorreta']);
            }

            // Atualizar senha
            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            
            Database::update('users', [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $user['id']]);

            $this->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso!'
            ]);

        } catch (\Exception $e) {
            $this->app->logger()->error('Erro ao alterar senha: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Erro interno. Tente novamente.'
            ], 500);
        }
    }

    public function uploadAvatar(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $user = $this->authService->getCurrentUser();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Falha no upload da imagem']);
        }

        $file = $_FILES['avatar'];
        if ($file['size'] > 3 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'Imagem muito grande (máx. 3MB)']);
        }

        $tmpPath = $file['tmp_name'];
        $mime = @mime_content_type($tmpPath) ?: '';
        $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Formato de imagem inválido']);
        }

        switch ($mime) {
            case 'image/png': $src = @imagecreatefrompng($tmpPath); break;
            case 'image/jpeg':
            case 'image/jpg': $src = @imagecreatefromjpeg($tmpPath); break;
            case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false; break;
            default: $src = false;
        }
        // Caminhos
        $root = dirname(__DIR__, 2);
        $docroot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
        $publicPath = $docroot !== '' ? $docroot : ($root . DIRECTORY_SEPARATOR . 'public');
        $uploadsDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
        if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0755, true); }
        // Se GD indisponível, salva arquivo original (mantendo extensão)
        if (!$src) {
            $ext = '.png';
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') $ext = '.jpg';
            elseif ($mime === 'image/webp') $ext = '.webp';
            // Apaga formatos anteriores
            foreach (['.png', '.jpg', '.jpeg', '.webp'] as $e) {
                $p = $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . $e;
                if (is_file($p)) @unlink($p);
            }
            $destRaw = $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . $ext;
            if (!@move_uploaded_file($tmpPath, $destRaw)) {
                // Tenta copy como fallback
                if (!@copy($tmpPath, $destRaw)) {
                    $this->json(['success' => false, 'message' => 'Não foi possível salvar a imagem enviada']);
                }
            }
            $url = '/uploads/avatars/' . $user['id'] . $ext . '?v=' . time();
            $this->json(['success' => true, 'message' => 'Foto de perfil atualizada!', 'url' => $url]);
        }

        $w = imagesx($src); $h = imagesy($src);
        $side = min($w, $h);
        $x = (int) max(0, ($w - $side) / 2);
        $y = (int) max(0, ($h - $side) / 2);
        $crop = imagecreatetruecolor($side, $side);
        imagealphablending($crop, false); imagesavealpha($crop, true);
        imagecopy($crop, $src, 0, 0, $x, $y, $side, $side);

        $dstSize = 256;
        $dst = imagecreatetruecolor($dstSize, $dstSize);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $crop, 0, 0, 0, 0, $dstSize, $dstSize, $side, $side);

        // Gera PNG 256x (crop + resize)
        $dest = $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.png';
        // Remove formatos antigos
        foreach (['.jpg', '.jpeg', '.webp'] as $e) {
            $p = $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . $e;
            if (is_file($p)) @unlink($p);
        }

        $ok = @imagepng($dst, $dest, 6);
        @imagedestroy($src); @imagedestroy($crop); @imagedestroy($dst);

        if (!$ok) {
            $this->json(['success' => false, 'message' => 'Erro ao salvar imagem']);
        }

        $url = '/uploads/avatars/' . $user['id'] . '.png?v=' . time();
        $this->json(['success' => true, 'message' => 'Foto de perfil atualizada!', 'url' => $url]);
    }

    public function deleteAvatar(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $user = $this->authService->getCurrentUser();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
        }

        $root = dirname(__DIR__, 2);
        $docroot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
        $publicPath = $docroot !== '' ? $docroot : ($root . DIRECTORY_SEPARATOR . 'public');
        $uploadsDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
        $deleted = false;
        foreach (['.png', '.jpg', '.jpeg', '.webp'] as $ext) {
            $p = $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . $ext;
            if (is_file($p)) {
                @unlink($p);
                $deleted = true;
            }
        }
        if ($deleted) {
            $this->json(['success' => true, 'message' => 'Foto de perfil removida']);
        }
        $this->json(['success' => false, 'message' => 'Nenhuma foto encontrada']);
    }
    
    /**
     * GET /api/profile/gamification
     * Retorna estatísticas de XP e Streak do usuário
     */
    public function getGamificationStats(): void
    {
        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->json(['success' => false, 'error' => 'Não autenticado'], 401);
            return;
        }
        
        try {
            $gamification = new \App\Services\GamificationService();
            $stats = $gamification->getUserStats((int)$user['id']);
            
            $this->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao buscar estatísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
