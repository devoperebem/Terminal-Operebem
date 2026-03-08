<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;
use App\Services\BunnyStorageService;
use App\Services\MediaOriginService;
use App\Services\PortalMaterialsConfigService;
use App\Services\PortalSyncService;

class AdminAlunoMaterialsController extends BaseController
{
    private PortalMaterialsConfigService $materialsService;
    private PortalSyncService $syncService;
    private BunnyStorageService $bunnyStorage;
    private MediaOriginService $mediaOrigin;

    public function __construct()
    {
        parent::__construct();
        $this->materialsService = new PortalMaterialsConfigService();
        $this->syncService = new PortalSyncService();
        $this->bunnyStorage = new BunnyStorageService();
        $this->mediaOrigin = new MediaOriginService();
    }

    public function index(): void
    {
        $selectedCourseId = (int)($_GET['course_id'] ?? 0);
        $courses = $this->fetchCourses();
        if ($selectedCourseId <= 0 && !empty($courses)) {
            $selectedCourseId = (int)($courses[0]['id'] ?? 0);
        }

        $materials = $selectedCourseId > 0
            ? $this->materialsService->allByCourse($selectedCourseId)
            : $this->materialsService->all();

        foreach ($materials as $idx => $material) {
            $materials[$idx]['preview_url'] = $this->buildPreviewUrl($material);
        }

        $this->view('admin_secure/aluno_materials_index', [
            'title' => 'Materiais do Portal do Aluno',
            'courses' => $courses,
            'selected_course_id' => $selectedCourseId,
            'materials' => $materials,
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function create(): void
    {
        $courseId = (int)($_GET['course_id'] ?? 0);
        $courses = $this->fetchCourses();
        if ($courseId <= 0 && !empty($courses)) {
            $courseId = (int)($courses[0]['id'] ?? 0);
        }

        $this->view('admin_secure/aluno_materials_form', [
            'title' => 'Novo Material',
            'material' => null,
            'courses' => $courses,
            'selected_course_id' => $courseId,
            'lessons' => $this->fetchLessons($courseId),
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function edit(): void
    {
        $id = trim((string)($_GET['id'] ?? ''));
        if ($id === '') {
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $material = $this->materialsService->find($id);
        if ($material === null) {
            $_SESSION['flash_error'] = 'Material nao encontrado.';
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $courseId = (int)$material['course_id'];
        $this->view('admin_secure/aluno_materials_form', [
            'title' => 'Editar Material',
            'material' => $material,
            'courses' => $this->fetchCourses(),
            'selected_course_id' => $courseId,
            'lessons' => $this->fetchLessons($courseId),
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $data = $this->collectMaterialData(null);
        if ($data === null) {
            return;
        }

        try {
            $created = $this->materialsService->create($data);
            $this->syncCourseMaterials((int)$created['course_id'], 'Material salvo e sincronizado.');
            $this->redirect('/secure/adm/aluno/materials?course_id=' . (int)$created['course_id']);
        } catch (\Throwable $e) {
            $this->logError('materials store', $e);
            $_SESSION['flash_error'] = 'Falha ao salvar material. Tente novamente.';
            $this->redirect('/secure/adm/aluno/materials/create?course_id=' . (int)($data['course_id'] ?? 0));
        }
    }

    public function update(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $id = trim((string)($_POST['id'] ?? ''));
        if ($id === '') {
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $existing = $this->materialsService->find($id);
        if ($existing === null) {
            $_SESSION['flash_error'] = 'Material nao encontrado para atualizacao.';
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $data = $this->collectMaterialData($existing);
        if ($data === null) {
            return;
        }

        try {
            $updated = $this->materialsService->update($id, $data);
            if ($updated === null) {
                $_SESSION['flash_error'] = 'Material nao encontrado para atualizacao.';
                $this->redirect('/secure/adm/aluno/materials');
                return;
            }
            $this->syncCourseMaterials((int)$updated['course_id'], 'Material atualizado e sincronizado.');
            $this->redirect('/secure/adm/aluno/materials?course_id=' . (int)$updated['course_id']);
        } catch (\Throwable $e) {
            $this->logError('materials update', $e);
            $_SESSION['flash_error'] = 'Falha ao atualizar material. Tente novamente.';
            $this->redirect('/secure/adm/aluno/materials/edit?id=' . urlencode($id));
        }
    }

    public function delete(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $id = trim((string)($_POST['id'] ?? ''));
        if ($id === '') {
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        try {
            $deleted = $this->materialsService->delete($id);
            if ($deleted === null) {
                $_SESSION['flash_error'] = 'Material nao encontrado para exclusao.';
                $this->redirect('/secure/adm/aluno/materials');
                return;
            }

            $courseId = (int)$deleted['course_id'];
            $this->syncCourseMaterials($courseId, 'Material removido e sincronizado.');
            $this->redirect('/secure/adm/aluno/materials?course_id=' . $courseId);
        } catch (\Throwable $e) {
            $this->logError('materials delete', $e);
            $_SESSION['flash_error'] = 'Falha ao excluir material. Tente novamente.';
            $this->redirect('/secure/adm/aluno/materials');
        }
    }

    public function syncCourse(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        if ($courseId <= 0) {
            $_SESSION['flash_error'] = 'Selecione um curso valido para sincronizar.';
            $this->redirect('/secure/adm/aluno/materials');
            return;
        }

        $this->syncCourseMaterials($courseId, 'Sincronizacao de materiais executada com sucesso.');
        $this->redirect('/secure/adm/aluno/materials?course_id=' . $courseId);
    }

    private function syncCourseMaterials(int $courseId, string $successMessage): void
    {
        $materials = $this->materialsService->buildSyncMaterialsForCourse($courseId);
        $user = $this->authService->getCurrentUser();
        $email = (string)($user['email'] ?? 'admin@local');

        $result = $this->syncService->syncMaterials($materials, [
            'synced_by' => $email,
            'source' => 'terminal-admin',
            'course_id' => $courseId,
            'synced_at' => date('c'),
        ]);

        if (!($result['success'] ?? false)) {
            $_SESSION['flash_error'] = 'Material salvo localmente, mas falhou no sync com Portal (status ' . (int)($result['status'] ?? 0) . ').';
            return;
        }

        $_SESSION['flash_success'] = $successMessage;
    }

    private function collectMaterialData(?array $existing): ?array
    {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $fileUrl = trim((string)($_POST['file_url'] ?? ''));
        $fileType = trim((string)($_POST['file_type'] ?? ''));
        $fileSize = (int)($_POST['file_size'] ?? 0);
        $isFree = isset($_POST['is_free']) && in_array((string)$_POST['is_free'], ['1', 'on', 'true'], true);
        $storagePath = trim((string)($existing['storage_path'] ?? ''));
        $storageDriver = trim((string)($existing['storage_driver'] ?? ''));

        if ($courseId <= 0 || $title === '') {
            $_SESSION['flash_error'] = 'Curso e titulo sao obrigatorios.';
            $this->redirect('/secure/adm/aluno/materials' . ($courseId > 0 ? '?course_id=' . $courseId : ''));
            return null;
        }

        $upload = $this->handleFileUpload();
        if ($upload['error'] !== null) {
            $_SESSION['flash_error'] = (string)$upload['error'];
            $this->redirect('/secure/adm/aluno/materials' . ($courseId > 0 ? '?course_id=' . $courseId : ''));
            return null;
        }

        if (!empty($upload['file_url'])) {
            $fileUrl = (string)$upload['file_url'];
            if (!empty($upload['file_type'])) {
                $fileType = (string)$upload['file_type'];
            }
            if (!empty($upload['file_size'])) {
                $fileSize = (int)$upload['file_size'];
            }
            if (!empty($upload['storage_path'])) {
                $storagePath = (string)$upload['storage_path'];
            }
            if (!empty($upload['storage_driver'])) {
                $storageDriver = (string)$upload['storage_driver'];
            }
        }

        if ($fileUrl === '' && is_array($existing)) {
            $fileUrl = (string)($existing['file_url'] ?? '');
            $fileType = $fileType !== '' ? $fileType : (string)($existing['file_type'] ?? '');
            if ($fileSize <= 0) {
                $fileSize = (int)($existing['file_size'] ?? 0);
            }
            if ($storagePath === '') {
                $storagePath = trim((string)($existing['storage_path'] ?? ''));
            }
            if ($storageDriver === '') {
                $storageDriver = trim((string)($existing['storage_driver'] ?? ''));
            }
        }

        if ($fileUrl === '') {
            $_SESSION['flash_error'] = 'Informe uma URL de arquivo ou envie um arquivo para upload.';
            $this->redirect('/secure/adm/aluno/materials' . ($courseId > 0 ? '?course_id=' . $courseId : ''));
            return null;
        }

        if ($fileType === '') {
            $path = parse_url($fileUrl, PHP_URL_PATH);
            $fileType = is_string($path) ? strtolower((string)pathinfo($path, PATHINFO_EXTENSION)) : 'pdf';
        }
        if ($fileType === '') {
            $fileType = 'pdf';
        }

        return [
            'course_id' => $courseId,
            'lesson_id' => $lessonId > 0 ? $lessonId : null,
            'title' => $title,
            'description' => $description,
            'file_url' => $fileUrl,
            'file_type' => strtolower($fileType),
            'file_size' => max(0, $fileSize),
            'is_free' => $isFree,
            'storage_path' => $storagePath,
            'storage_driver' => $storageDriver,
        ];
    }

    private function handleFileUpload(): array
    {
        if (!isset($_FILES['file'])) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => null];
        }

        $file = $_FILES['file'];
        if (!is_array($file)) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Arquivo invalido no upload.'];
        }

        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => null];
        }

        if ((int)($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Falha no upload do arquivo.'];
        }

        if (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Upload nao validado pelo servidor.'];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Arquivo vazio.'];
        }
        if ($size > 50 * 1024 * 1024) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Arquivo excede o limite de 50 MB.'];
        }

        $originalName = (string)($file['name'] ?? '');
        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExt = ['pdf', 'xlsx', 'xls', 'ppt', 'pptx', 'doc', 'docx', 'zip', 'csv', 'txt'];
        if ($extension === '' || !in_array($extension, $allowedExt, true)) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Tipo de arquivo nao permitido.'];
        }

        $publicPath = dirname(__DIR__, 2) . '/public';
        $targetDir = $publicPath . '/uploads/aluno/materials';
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true)) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Nao foi possivel criar diretorio de upload.'];
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', (string)pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = trim((string)$safeBase, '-');
        if ($safeBase === '') {
            $safeBase = 'material';
        }

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (\Throwable $__) {
            $suffix = substr(sha1((string)microtime(true)), 0, 8);
        }

        $filename = date('YmdHis') . '-' . $safeBase . '-' . $suffix . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;
        if (!@move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
            return ['file_url' => null, 'file_type' => null, 'file_size' => null, 'storage_path' => null, 'storage_driver' => null, 'error' => 'Nao foi possivel salvar o arquivo enviado.'];
        }

        $relative = '/uploads/aluno/materials/' . $filename;
        $appUrl = rtrim((string)($_ENV['APP_URL'] ?? ''), '/');
        $fileUrl = $appUrl !== '' ? ($appUrl . $relative) : $relative;
        $storagePath = trim($relative, '/');
        $storageDriver = 'local';

        $isFree = isset($_POST['is_free']) && in_array((string)$_POST['is_free'], ['1', 'on', 'true'], true);
        if ($this->mediaOrigin->isUploadConfigured()) {
            $originUpload = $this->mediaOrigin->upload($targetPath, $originalName, $isFree);
            if (!empty($originUpload['success']) && !empty($originUpload['file_url'])) {
                $fileUrl = (string)$originUpload['file_url'];
                $storagePath = trim((string)($originUpload['storage_path'] ?? ''), '/');
                $storageDriver = 'hostinger';
                @unlink($targetPath);
            } else {
                try {
                    Application::getInstance()->logger()->warning('Falha upload Hostinger media origin; mantendo fluxo de fallback', [
                        'error' => (string)($originUpload['error'] ?? ''),
                        'filename' => $filename,
                    ]);
                } catch (\Throwable $__) {
                }
            }
        }

        if ($storageDriver === 'local' && $this->bunnyStorage->isConfigured()) {
            $remotePath = 'materials/' . date('Y/m') . '/' . $filename;
            $uploadResult = $this->bunnyStorage->upload($targetPath, $remotePath);
            if (!empty($uploadResult['success']) && !empty($uploadResult['url'])) {
                $fileUrl = (string)$uploadResult['url'];
                $storagePath = $remotePath;
                $storageDriver = 'bunny';
                @unlink($targetPath);
            } else {
                try {
                    Application::getInstance()->logger()->warning('Falha upload Bunny para material; mantendo arquivo local', [
                        'error' => (string)($uploadResult['error'] ?? ''),
                        'filename' => $filename,
                    ]);
                } catch (\Throwable $__) {
                }
            }
        }

        return [
            'file_url' => $fileUrl,
            'file_type' => $extension,
            'file_size' => $size,
            'storage_path' => $storagePath,
            'storage_driver' => $storageDriver,
            'error' => null,
        ];
    }

    private function buildPreviewUrl(array $material): string
    {
        $fileUrl = trim((string)($material['file_url'] ?? ''));
        if ($fileUrl === '') {
            return '';
        }

        $isFree = !empty($material['is_free']);
        if ($isFree) {
            return $fileUrl;
        }

        $storagePath = trim((string)($material['storage_path'] ?? ''), '/');
        if ($storagePath === '') {
            return $fileUrl;
        }

        $signed = $this->mediaOrigin->signDownloadUrl($storagePath);
        if ($signed === '') {
            return $fileUrl;
        }

        return $signed;
    }

    private function fetchCourses(): array
    {
        try {
            return Database::fetchAll('SELECT id, title FROM courses ORDER BY COALESCE(position, id) ASC, id ASC', [], 'aluno');
        } catch (\Throwable $__) {
            return [];
        }
    }

    private function fetchLessons(int $courseId): array
    {
        if ($courseId <= 0) {
            return [];
        }

        try {
            return Database::fetchAll('SELECT id, title FROM lessons WHERE course_id = ? ORDER BY position ASC, id ASC', [$courseId], 'aluno');
        } catch (\Throwable $__) {
            return [];
        }
    }

    private function logError(string $context, \Throwable $e): void
    {
        try {
            Application::getInstance()->logger()->error('Aluno materials ' . $context . ' fail: ' . $e->getMessage());
        } catch (\Throwable $__) {
        }
    }
}
