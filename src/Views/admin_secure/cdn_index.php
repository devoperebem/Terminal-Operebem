<?php ob_start(); ?>
<style>
.cdn-dash { max-width: 1280px; margin: 0 auto; padding: 1.5rem 1rem; }
@media (min-width: 768px) { .cdn-dash { padding: 2rem; } }
.cdn-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
.cdn-header h1 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0; }
.cdn-status { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; }
.cdn-status .dot { width: 10px; height: 10px; border-radius: 50%; }
.cdn-status .dot.ok { background: #22c55e; }
.cdn-status .dot.err { background: #ef4444; }
.cdn-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 1.5rem; }
.cdn-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.cdn-card-header h2 { font-size: 1rem; font-weight: 600; margin: 0; }
.cdn-card-body { padding: 1.25rem; }
.category-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
.category-tab { padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); cursor: pointer; font-size: 0.85rem; transition: all 0.15s; }
.category-tab:hover, .category-tab.active { background: #667eea; color: #fff; border-color: #667eea; }
.files-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.file-item { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; position: relative; }
.file-item .file-name { font-size: 0.8rem; font-weight: 500; word-break: break-all; margin-bottom: 0.5rem; }
.file-item .file-meta { font-size: 0.7rem; color: var(--text-secondary); }
.file-item .file-actions { margin-top: 0.75rem; display: flex; gap: 0.5rem; }
.file-item .badge { font-size: 0.65rem; padding: 0.2rem 0.5rem; border-radius: 4px; }
.badge-global { background: #22c55e; color: #fff; }
.badge-restricted { background: #f59e0b; color: #fff; }
.badge-protected { background: #ef4444; color: #fff; }
.upload-form { display: grid; gap: 1rem; }
@media (min-width: 768px) { .upload-form { grid-template-columns: 1fr 1fr auto; align-items: end; } }
.form-group label { display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.375rem; }
.form-group input, .form-group select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; background: var(--card-bg); color: var(--text-primary); }
.btn-cdn { padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; border: none; cursor: pointer; transition: all 0.15s; }
.btn-cdn-primary { background: #667eea; color: #fff; }
.btn-cdn-primary:hover { background: #5a6fd6; }
.btn-cdn-danger { background: #ef4444; color: #fff; }
.btn-cdn-danger:hover { background: #dc2626; }
.btn-cdn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.empty-state { text-align: center; padding: 2rem; color: var(--text-secondary); }
.empty-state i { font-size: 2rem; margin-bottom: 0.5rem; display: block; }
.alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.85rem; }
.alert-success { background: rgba(34,197,94,0.1); border: 1px solid #22c55e; color: #22c55e; }
.alert-error { background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; }
</style>

<div class="cdn-dash">
    <div class="cdn-header">
        <h1><i class="fas fa-cloud me-2"></i>CDN Operebem</h1>
        <div class="cdn-status">
            <?php if ($cdnConfigured && !empty($cdnStatus['success'])): ?>
                <span class="dot ok"></span>
                <span>Online</span>
            <?php else: ?>
                <span class="dot err"></span>
                <span><?= $cdnConfigured ? 'Offline' : 'Não configurado' ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!$cdnConfigured): ?>
        <div class="cdn-card">
            <div class="cdn-card-body">
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>CDN não configurado. Adicione <code>CDN_BASE_URL</code> e <code>CDN_API_KEY</code> no arquivo .env</p>
                </div>
            </div>
        </div>
    <?php else: ?>

    <div class="cdn-card">
        <div class="cdn-card-header">
            <h2><i class="fas fa-upload me-2"></i>Upload de Arquivo</h2>
        </div>
        <div class="cdn-card-body">
            <form action="/secure/adm/cdn/upload" method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div class="form-group">
                    <label for="category">Categoria</label>
                    <select name="category" id="category" required>
                        <option value="">Selecione...</option>
                        <option value="global">🌍 Global (acesso de qualquer lugar)</option>
                        <option value="logos">🏷️ Logos (restrito a Operebem)</option>
                        <option value="watermarks">💧 Watermarks (restrito)</option>
                        <option value="avatars">👤 Avatars (restrito)</option>
                        <option value="materials">📄 Materiais (protegido com token)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file">Arquivo</label>
                    <input type="file" name="file" id="file" required>
                </div>
                <button type="submit" class="btn-cdn btn-cdn-primary">
                    <i class="fas fa-cloud-upload-alt me-1"></i>Enviar
                </button>
            </form>
        </div>
    </div>

    <div class="cdn-card">
        <div class="cdn-card-header">
            <h2><i class="fas fa-folder-open me-2"></i>Arquivos</h2>
            <form action="/secure/adm/cdn/cleanup" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn-cdn btn-cdn-sm btn-cdn-danger" onclick="return confirm('Limpar tokens expirados?')">
                    <i class="fas fa-broom me-1"></i>Limpar Expirados
                </button>
            </form>
        </div>
        <div class="cdn-card-body">
            <div class="category-tabs" id="categoryTabs">
                <button class="category-tab active" data-cat="all">Todos</button>
                <button class="category-tab" data-cat="global">🌍 Global</button>
                <button class="category-tab" data-cat="logos">🏷️ Logos</button>
                <button class="category-tab" data-cat="watermarks">💧 Watermarks</button>
                <button class="category-tab" data-cat="avatars">👤 Avatars</button>
                <button class="category-tab" data-cat="materials">📄 Materiais</button>
            </div>

            <?php 
            $allEmpty = true;
            foreach ($categories as $cat => $files):
                if (!empty($files)) $allEmpty = false;
            endforeach;
            ?>

            <?php if ($allEmpty): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhum arquivo encontrado</p>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $cat => $files): ?>
                    <?php if (!empty($files)): ?>
                        <div class="files-section" data-category="<?= htmlspecialchars($cat) ?>" style="margin-bottom: 1.5rem;">
                            <h3 style="font-size: 0.9rem; margin-bottom: 0.75rem; color: var(--text-secondary);">
                                <?= ucfirst(htmlspecialchars($cat)) ?> (<?= count($files) ?>)
                            </h3>
                            <div class="files-grid">
                                <?php foreach ($files as $file): ?>
                                    <div class="file-item">
                                        <span class="badge badge-<?= htmlspecialchars($file['access_type'] ?? 'restricted') ?>">
                                            <?= htmlspecialchars($file['access_type'] ?? 'restricted') ?>
                                        </span>
                                        <div class="file-name" title="<?= htmlspecialchars($file['name']) ?>">
                                            <?= htmlspecialchars(strlen($file['name']) > 30 ? substr($file['name'], 0, 27) . '...' : $file['name']) ?>
                                        </div>
                                        <div class="file-meta">
                                            <?= number_format(($file['size'] ?? 0) / 1024, 1) ?> KB
                                        </div>
                                        <div class="file-actions">
                                            <?php if (!empty($file['url'])): ?>
                                                <a href="<?= htmlspecialchars($cdnBaseUrl . $file['url']) ?>" target="_blank" class="btn-cdn btn-cdn-sm btn-cdn-primary">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button type="button" class="btn-cdn btn-cdn-sm btn-cdn-primary" onclick="copyToClipboard('<?= htmlspecialchars($cdnBaseUrl . $file['url']) ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php endif; ?>
                                            <form action="/secure/adm/cdn/delete" method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                <input type="hidden" name="category" value="<?= htmlspecialchars($cat) ?>">
                                                <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                                                <button type="submit" class="btn-cdn btn-cdn-sm btn-cdn-danger" onclick="return confirm('Deletar este arquivo?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('URL copiada!');
    }).catch(() => {
        prompt('Copie a URL:', text);
    });
}

document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const cat = this.dataset.cat;
        document.querySelectorAll('.files-section').forEach(section => {
            if (cat === 'all' || section.dataset.category === cat) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
