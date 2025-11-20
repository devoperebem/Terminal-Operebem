<?php
$title = 'Gestão de Reviews';
$csrf_token = $_SESSION['csrf_token'] ?? '';
ob_start();
?>

<style>
  .review-card { transition: all 0.2s ease; border-left: 3px solid transparent; }
  .review-card.inactive { opacity: 0.6; }
  .review-card.active { border-left-color: #22c55e; }
  .rating-stars { color: #f59e0b; }
  .drag-handle { cursor: move; color: #9ca3af; }
  .review-actions .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
</style>

<div class="container my-4">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="fas fa-star me-2"></i>Reviews & Feedbacks</h1>
    <button class="btn btn-primary btn-sm" id="btnNewReview">
      <i class="fas fa-plus me-2"></i>Novo Review
    </button>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(34,197,94,0.15);color:#22c55e;">
              <i class="fas fa-star fa-lg"></i>
            </div>
            <div>
              <div class="text-muted small">Total de Reviews</div>
              <div class="fw-bold fs-4" id="totalReviews">0</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(59,130,246,0.15);color:#3b82f6;">
              <i class="fas fa-check-circle fa-lg"></i>
            </div>
            <div>
              <div class="text-muted small">Ativos</div>
              <div class="fw-bold fs-4" id="activeReviews">0</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(251,191,36,0.15);color:#f59e0b;">
              <i class="fas fa-chart-line fa-lg"></i>
            </div>
            <div>
              <div class="text-muted small">Rating Médio</div>
              <div class="fw-bold fs-4" id="avgRating">0.0</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(239,68,68,0.15);color:#ef4444;">
              <i class="fas fa-times-circle fa-lg"></i>
            </div>
            <div>
              <div class="text-muted small">Inativos</div>
              <div class="fw-bold fs-4" id="inactiveReviews">0</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs: Reviews e Feedbacks -->
  <div class="card">
    <div class="card-header">
      <ul class="nav nav-tabs card-header-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-reviews" data-bs-toggle="tab" data-bs-target="#content-reviews" type="button" role="tab">
            <i class="fas fa-star me-2"></i>Reviews Públicos
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-feedbacks" data-bs-toggle="tab" data-bs-target="#content-feedbacks" type="button" role="tab">
            <i class="fas fa-comment-dots me-2"></i>Feedbacks de Usuários
          </button>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content">
        <!-- Tab: Reviews -->
        <div class="tab-pane fade show active" id="content-reviews" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Reviews Públicos</h5>
            <button class="btn btn-sm btn-outline-secondary" id="btnReorderMode">
              <i class="fas fa-sort me-2"></i>Reordenar
            </button>
          </div>
          <div id="reviewsList"></div>
        </div>
        
        <!-- Tab: Feedbacks -->
        <div class="tab-pane fade" id="content-feedbacks" role="tabpanel">
          <div id="feedbacksList"></div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Modal: Novo/Editar Review -->
<div class="modal fade" id="modalReview" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalReviewTitle">Novo Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formReview">
          <input type="hidden" id="reviewId" name="id">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome do Autor *</label>
              <input type="text" class="form-control" id="authorName" name="author_name" required maxlength="150">
            </div>
            <div class="col-md-6">
              <label class="form-label">País</label>
              <input type="text" class="form-control" id="authorCountry" name="author_country" maxlength="100" placeholder="Brasil">
            </div>
            <div class="col-md-6">
              <label class="form-label">Avatar URL</label>
              <input type="url" class="form-control" id="authorAvatar" name="author_avatar" maxlength="500" placeholder="https://...">
              <small class="text-muted">URL da imagem do avatar (ex: Pravatar, Gravatar)</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Rating *</label>
              <select class="form-select" id="rating" name="rating" required>
                <option value="5.0">5.0 ⭐⭐⭐⭐⭐</option>
                <option value="4.5">4.5 ⭐⭐⭐⭐½</option>
                <option value="4.0">4.0 ⭐⭐⭐⭐</option>
                <option value="3.5">3.5 ⭐⭐⭐½</option>
                <option value="3.0">3.0 ⭐⭐⭐</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Texto do Review *</label>
              <textarea class="form-control" id="reviewText" name="review_text" required rows="4" maxlength="1000"></textarea>
              <small class="text-muted"><span id="charCount">0</span>/1000 caracteres</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ordem de Exibição</label>
              <input type="number" class="form-control" id="displayOrder" name="display_order" value="0" min="0">
              <small class="text-muted">Menor número = maior prioridade</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                <label class="form-check-label" for="isActive">Ativo (visível na home)</label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSaveReview">Salvar</button>
      </div>
    </div>
  </div>
</div>

<script>
const API_URL = '/api/admin/reviews';
const CSRF_TOKEN = '<?= $csrf_token ?>';

let reviews = [];
let reorderMode = false;

// ============================================================================
// CARREGAR REVIEWS
// ============================================================================

async function loadReviews() {
  try {
    const res = await fetch(API_URL, {
      headers: { 'X-CSRF-Token': CSRF_TOKEN }
    });
    const data = await res.json();
    
    if (data.success) {
      reviews = data.data;
      renderReviews();
      updateStats();
    } else {
      showAlert('Erro ao carregar reviews: ' + (data.error || 'Desconhecido'), 'danger');
    }
  } catch (err) {
    console.error(err);
    showAlert('Erro ao carregar reviews', 'danger');
  }
}

// ============================================================================
// RENDERIZAR REVIEWS
// ============================================================================

function renderReviews() {
  const container = document.getElementById('reviewsList');
  
  if (reviews.length === 0) {
    container.innerHTML = '<p class="text-muted text-center py-5">Nenhum review cadastrado</p>';
    return;
  }
  
  reviews.sort((a, b) => a.display_order - b.display_order);
  
  container.innerHTML = reviews.map(r => `
    <div class="review-card card mb-3 ${r.is_active ? 'active' : 'inactive'}" data-id="${r.id}">
      <div class="card-body">
        <div class="d-flex align-items-start">
          ${reorderMode ? '<div class="drag-handle me-3 mt-2"><i class="fas fa-grip-vertical"></i></div>' : ''}
          <img src="${r.author_avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(r.author_name)}" 
               alt="${r.author_name}" class="rounded-circle me-3" width="60" height="60">
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h6 class="mb-1">${escapeHtml(r.author_name)}</h6>
                <div class="text-muted small mb-1">${escapeHtml(r.author_country || 'País não informado')}</div>
                <div class="rating-stars">${renderStars(r.rating)}</div>
              </div>
              <div class="review-actions d-flex gap-2">
                <span class="badge ${r.is_active ? 'bg-success' : 'bg-secondary'}">
                  ${r.is_active ? 'Ativo' : 'Inativo'}
                </span>
                <button class="btn btn-sm btn-outline-primary" onclick="editReview(${r.id})" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-${r.is_active ? 'warning' : 'success'}" 
                        onclick="toggleReview(${r.id}, ${r.is_active})" title="${r.is_active ? 'Desativar' : 'Ativar'}">
                  <i class="fas fa-${r.is_active ? 'eye-slash' : 'eye'}"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteReview(${r.id})" title="Excluir">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <p class="mb-2">${escapeHtml(r.review_text)}</p>
            <div class="text-muted small">
              Ordem: ${r.display_order} | Criado: ${formatDate(r.created_at)}
            </div>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}

function renderStars(rating) {
  const r = parseFloat(rating);
  const full = Math.floor(r);
  const half = (r % 1) >= 0.5 ? 1 : 0;
  const empty = 5 - full - half;
  
  return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
}

// ============================================================================
// ESTATÍSTICAS
// ============================================================================

function updateStats() {
  const total = reviews.length;
  const active = reviews.filter(r => r.is_active).length;
  const inactive = total - active;
  const avg = total > 0 ? (reviews.reduce((sum, r) => sum + parseFloat(r.rating), 0) / total).toFixed(1) : '0.0';
  
  document.getElementById('totalReviews').textContent = total;
  document.getElementById('activeReviews').textContent = active;
  document.getElementById('inactiveReviews').textContent = inactive;
  document.getElementById('avgRating').textContent = avg;
}

// ============================================================================
// MODAL: NOVO/EDITAR
// ============================================================================

document.getElementById('btnNewReview').addEventListener('click', () => {
  document.getElementById('modalReviewTitle').textContent = 'Novo Review';
  document.getElementById('formReview').reset();
  document.getElementById('reviewId').value = '';
  document.getElementById('isActive').checked = true;
  new bootstrap.Modal(document.getElementById('modalReview')).show();
});

async function editReview(id) {
  const review = reviews.find(r => r.id === id);
  if (!review) return;
  
  document.getElementById('modalReviewTitle').textContent = 'Editar Review';
  document.getElementById('reviewId').value = review.id;
  document.getElementById('authorName').value = review.author_name;
  document.getElementById('authorCountry').value = review.author_country || '';
  document.getElementById('authorAvatar').value = review.author_avatar || '';
  document.getElementById('rating').value = review.rating;
  document.getElementById('reviewText').value = review.review_text;
  document.getElementById('displayOrder').value = review.display_order;
  document.getElementById('isActive').checked = review.is_active;
  
  new bootstrap.Modal(document.getElementById('modalReview')).show();
}

// Contador de caracteres
document.getElementById('reviewText').addEventListener('input', (e) => {
  document.getElementById('charCount').textContent = e.target.value.length;
});

// Salvar
document.getElementById('btnSaveReview').addEventListener('click', async () => {
  const form = document.getElementById('formReview');
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  
  const id = document.getElementById('reviewId').value;
  const data = {
    author_name: document.getElementById('authorName').value,
    author_country: document.getElementById('authorCountry').value || null,
    author_avatar: document.getElementById('authorAvatar').value || null,
    rating: parseFloat(document.getElementById('rating').value),
    review_text: document.getElementById('reviewText').value,
    display_order: parseInt(document.getElementById('displayOrder').value),
    is_active: document.getElementById('isActive').checked ? 1 : 0
  };
  
  try {
    const method = id ? 'PUT' : 'POST';
    const url = id ? `${API_URL}/${id}` : API_URL;
    
    const res = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF_TOKEN
      },
      body: JSON.stringify(data)
    });
    
    const result = await res.json();
    
    if (result.success) {
      showAlert(id ? 'Review atualizado!' : 'Review criado!', 'success');
      bootstrap.Modal.getInstance(document.getElementById('modalReview')).hide();
      loadReviews();
    } else {
      showAlert('Erro: ' + (result.error || 'Desconhecido'), 'danger');
    }
  } catch (err) {
    console.error(err);
    showAlert('Erro ao salvar review', 'danger');
  }
});

// ============================================================================
// AÇÕES
// ============================================================================

async function toggleReview(id, currentStatus) {
  if (!confirm(`Deseja ${currentStatus ? 'desativar' : 'ativar'} este review?`)) return;
  
  try {
    const res = await fetch(`${API_URL}/${id}/toggle`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF_TOKEN
      }
    });
    
    const data = await res.json();
    
    if (data.success) {
      showAlert('Status alterado!', 'success');
      loadReviews();
    } else {
      showAlert('Erro: ' + (data.error || 'Desconhecido'), 'danger');
    }
  } catch (err) {
    console.error(err);
    showAlert('Erro ao alterar status', 'danger');
  }
}

async function deleteReview(id) {
  if (!confirm('Deseja realmente excluir este review? Esta ação não pode ser desfeita.')) return;
  
  try {
    const res = await fetch(`${API_URL}/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-Token': CSRF_TOKEN }
    });
    
    const data = await res.json();
    
    if (data.success) {
      showAlert('Review excluído!', 'success');
      loadReviews();
    } else {
      showAlert('Erro: ' + (data.error || 'Desconhecido'), 'danger');
    }
  } catch (err) {
    console.error(err);
    showAlert('Erro ao excluir review', 'danger');
  }
}

// ============================================================================
// HELPERS
// ============================================================================

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR');
}

function showAlert(message, type = 'info') {
  const alert = document.createElement('div');
  alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
  alert.style.zIndex = '9999';
  alert.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  document.body.appendChild(alert);
  setTimeout(() => alert.remove(), 5000);
}

// ============================================================================
// FEEDBACKS
// ============================================================================

let feedbacks = [];

async function loadFeedbacks() {
  try {
    const res = await fetch('/api/admin/feedbacks', {
      headers: { 'X-CSRF-Token': CSRF_TOKEN }
    });
    const data = await res.json();
    
    if (data.success) {
      feedbacks = data.data;
      renderFeedbacks();
    }
  } catch (err) {
    console.error(err);
  }
}

function renderFeedbacks() {
  const container = document.getElementById('feedbacksList');
  
  if (feedbacks.length === 0) {
    container.innerHTML = '<p class="text-muted text-center py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>Nenhum feedback recebido ainda</p>';
    return;
  }
  
  container.innerHTML = feedbacks.map(f => `
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="mb-1">
              <i class="fas fa-user-circle me-2 text-primary"></i>
              ${escapeHtml(f.user_name || 'Usuário')}
            </h5>
            <small class="text-muted">
              <i class="fas fa-envelope me-1"></i>
              ${escapeHtml(f.user_email || 'N/A')}
            </small>
          </div>
          <div class="text-end">
            <div class="mb-2">
              ${renderStarsFromInt(f.rating)}
              <span class="badge bg-primary ms-2">${f.rating}/5</span>
            </div>
            <small class="text-muted">
              <i class="fas fa-clock me-1"></i>
              ${formatDate(f.created_at)}
            </small>
          </div>
        </div>

        <div class="mb-3">
          <strong><i class="fas fa-comment me-2"></i>Comentário:</strong>
          <p class="mt-2 mb-0">${escapeHtml(f.comment).replace(/\n/g, '<br>')}</p>
        </div>

        ${f.q1_like_most || f.q2_improve || f.q3_recommend || f.q4_feature_request || f.q5_support_quality ? `
          <hr>
          <div class="row g-3 small">
            ${f.q1_like_most ? `
              <div class="col-md-6">
                <strong class="text-success"><i class="fas fa-heart me-2"></i>O que mais gosta:</strong>
                <p class="mb-0">${escapeHtml(f.q1_like_most)}</p>
              </div>
            ` : ''}
            ${f.q2_improve ? `
              <div class="col-md-6">
                <strong class="text-warning"><i class="fas fa-wrench me-2"></i>Pode melhorar:</strong>
                <p class="mb-0">${escapeHtml(f.q2_improve)}</p>
              </div>
            ` : ''}
            ${f.q3_recommend ? `
              <div class="col-md-4">
                <strong><i class="fas fa-share me-2"></i>Recomendaria:</strong>
                <p class="mb-0"><span class="badge bg-${getRecommendColor(f.q3_recommend)}">${getRecommendLabel(f.q3_recommend)}</span></p>
              </div>
            ` : ''}
            ${f.q4_feature_request ? `
              <div class="col-md-4">
                <strong class="text-info"><i class="fas fa-lightbulb me-2"></i>Funcionalidade:</strong>
                <p class="mb-0">${escapeHtml(f.q4_feature_request)}</p>
              </div>
            ` : ''}
            ${f.q5_support_quality ? `
              <div class="col-md-4">
                <strong><i class="fas fa-headset me-2"></i>Suporte:</strong>
                <p class="mb-0"><span class="badge bg-${getSupportColor(f.q5_support_quality)}">${getSupportLabel(f.q5_support_quality)}</span></p>
              </div>
            ` : ''}
          </div>
        ` : ''}

        <hr>
        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            ID: #${f.id} | IP: ${escapeHtml(f.ip_address || 'N/A')}
          </small>
          <button class="btn btn-sm btn-outline-success" onclick="promoteFeedback(${f.id})">
            <i class="fas fa-star me-2"></i>Promover para Review Público
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function renderStarsFromInt(rating) {
  const r = parseInt(rating);
  return '⭐'.repeat(r) + '☆'.repeat(5 - r);
}

function getRecommendLabel(val) {
  const labels = {
    'definitely': 'Definitivamente',
    'probably': 'Provavelmente',
    'maybe': 'Talvez',
    'probably_not': 'Provavelmente não',
    'definitely_not': 'Definitivamente não'
  };
  return labels[val] || val;
}

function getRecommendColor(val) {
  const colors = {
    'definitely': 'success',
    'probably': 'info',
    'maybe': 'warning',
    'probably_not': 'danger',
    'definitely_not': 'danger'
  };
  return colors[val] || 'secondary';
}

function getSupportLabel(val) {
  const labels = {
    'excellent': 'Excelente',
    'good': 'Bom',
    'average': 'Regular',
    'poor': 'Ruim',
    'not_used': 'Não utilizou'
  };
  return labels[val] || val;
}

function getSupportColor(val) {
  const colors = {
    'excellent': 'success',
    'good': 'info',
    'average': 'warning',
    'poor': 'danger',
    'not_used': 'secondary'
  };
  return colors[val] || 'secondary';
}

async function promoteFeedback(feedbackId) {
  if (!confirm('Deseja promover este feedback para review público?\n\nO review será criado como INATIVO e você poderá editá-lo antes de publicar.')) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append('feedback_id', feedbackId);
    formData.append('csrf_token', CSRF_TOKEN);

    const response = await fetch('/secure/adm/feedback/promote', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const data = await response.json();

    if (data.success) {
      showAlert(data.message || 'Feedback promovido com sucesso!', 'success');
      loadReviews();
      loadFeedbacks();
    } else {
      showAlert(data.error || 'Erro ao promover feedback', 'danger');
    }
  } catch (error) {
    console.error('Erro:', error);
    showAlert('Erro ao promover feedback. Tente novamente.', 'danger');
  }
}

// Carregar ao iniciar
loadReviews();
loadFeedbacks();

// Recarregar feedbacks ao trocar de aba
document.getElementById('tab-feedbacks').addEventListener('shown.bs.tab', function() {
  loadFeedbacks();
});
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
