<!DOCTYPE html>
<html lang="pt-BR" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro interno - Terminal Operebem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 text-center">
                    <div class="error-content">
                        <div class="error-icon mb-4">
                            <i class="fas fa-exclamation-triangle fa-5x text-warning"></i>
                        </div>
                        <h1 class="display-1 fw-bold text-danger">500</h1>
                        <h2 class="h4 mb-3">Erro interno do servidor</h2>
                        <p class="text-muted mb-4">
                            Ocorreu um erro interno. Nossa equipe foi notificada e está trabalhando para resolver o problema.
                        </p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="/" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Página Inicial
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt me-2"></i>Tentar Novamente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
