# 🧪 Sistema de Ambiente de Teste `/dev/`

## 📋 Visão Geral

Este sistema permite criar **versões de teste de páginas** sem afetar a produção. Você pode testar modificações, novos designs ou funcionalidades em um ambiente isolado antes de implantar em produção.

---

## 🚀 Como Funciona

### Conceito Básico

- **URL de Produção**: `https://terminal.operebem.com.br/app/dashboard/gold`
- **URL de Teste**: `https://terminal.operebem.com.br/dev/app/dashboard/gold`
  
A diferença é apenas o prefixo `/dev/` na URL.

### Sistema de Fallback Automático

1. Quando você acessa uma URL com `/dev/`, o sistema:
   - ✅ **Procura** uma versão de teste em `src/Views/dev/app/dashboard-gold.php`
   - ✅ **Se encontrar**: Usa a versão de TESTE
   - ✅ **Se NÃO encontrar**: Usa a versão de PRODUÇÃO (fallback automático)

2. URLs sem `/dev/` sempre usam a versão de produção.

---

## 📁 Estrutura de Arquivos

```
src/
├── Views/
│   ├── app/
│   │   └── dashboard-gold.php          ← PRODUÇÃO
│   └── dev/                             ← PASTA DE TESTE
│       └── app/
│           └── dashboard-gold.php      ← TESTE (opcional)
```

**Regras:**
- ✅ Só crie arquivos em `dev/` quando precisar de versão de teste
- ✅ A estrutura de pastas deve espelhar a de produção
- ✅ Se não existir versão de teste, usa produção automaticamente

---

## 🛠️ Como Criar uma Versão de Teste

### Exemplo: Dashboard de Ouro

#### Passo 1: Criar estrutura de pastas

```bash
# Criar pasta se não existir
mkdir -p "src/Views/dev/app"
```

#### Passo 2: Copiar versão de produção

```bash
# Copiar arquivo de produção como base
cp "src/Views/app/dashboard-gold.php" "src/Views/dev/app/dashboard-gold.php"
```

#### Passo 3: Modificar a versão de teste

Edite `src/Views/dev/app/dashboard-gold.php` e adicione suas alterações.

**Exemplo - Adicionar banner de teste:**

```php
<?php
$title = 'Dashboard Ouro [TESTE] - Terminal Operebem';
$csrf_token = $_SESSION['csrf_token'] ?? '';

ob_start();
?>

<!-- 🧪 BANNER DE AMBIENTE DE TESTE -->
<div class="alert alert-warning m-3 text-center fw-bold shadow-sm" 
     style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); 
            color: #000; 
            border: 3px dashed #ff5722; 
            border-radius: 12px;">
  <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
    <span style="font-size: 2rem;">🧪</span>
    <div>
      <div class="fs-4">AMBIENTE DE TESTE</div>
      <div class="small mt-1">
        Esta é uma versão de desenvolvimento (/dev/). 
        Alterações aqui NÃO afetam a produção.
      </div>
    </div>
    <span style="font-size: 2rem;">🧪</span>
  </div>
</div>

<!-- Resto do conteúdo da página -->
```

#### Passo 4: Testar

```
# Produção (sem alterações)
https://terminal.operebem.com.br/app/dashboard/gold

# Teste (com suas modificações)
https://terminal.operebem.com.br/dev/app/dashboard/gold
```

#### Passo 5: Aplicar em produção (quando estiver pronto)

```bash
# Quando estiver satisfeito com o teste, simplesmente copie de volta:
cp "src/Views/dev/app/dashboard-gold.php" "src/Views/app/dashboard-gold.php"

# Ou aplique suas modificações manualmente na versão de produção
```

---

## ⚙️ Componentes do Sistema

### 1. Detecção de Ambiente (`public/index.php`)

```php
// Detecta se a URL começa com /dev/
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

if (str_starts_with($path, '/dev/')) {
    define('IS_DEV_ENVIRONMENT', true);
    
    // Remove o prefixo /dev/ da URI
    $cleanPath = substr($path, 4);
    $_SERVER['REQUEST_URI'] = $cleanPath . ($queryString !== '' ? '?' . $queryString : '');
    
    header('X-Dev-Environment: true');
} else {
    define('IS_DEV_ENVIRONMENT', false);
}
```

### 2. Helper de Fallback (`src/Helpers/dev_helpers.php`)

```php
function get_dev_view_path(string $viewPath): ?string
{
    $basePath = dirname(__DIR__) . '/Views/';
    
    // Se estiver em ambiente dev, tenta versão dev primeiro
    if (is_dev_environment()) {
        $devPath = $basePath . 'dev/' . $viewPath . '.php';
        if (file_exists($devPath)) {
            return $devPath;  // Retorna versão de TESTE
        }
    }
    
    // Fallback para versão de produção
    $prodPath = $basePath . $viewPath . '.php';
    if (file_exists($prodPath)) {
        return $prodPath;  // Retorna versão de PRODUÇÃO
    }
    
    return null;
}
```

### 3. Controller Base (`src/Controllers/BaseController.php`)

```php
protected function view(string $view, array $data = []): void
{
    // ... código de preparação dos dados ...
    
    // Tentar obter caminho da view com fallback automático
    $viewPath = get_dev_view_path($view);
    
    // Fallback para caminho tradicional se helper não encontrar
    if ($viewPath === null) {
        $viewPath = dirname(__DIR__, 2) . "/src/Views/{$view}.php";
    }
    
    if (!file_exists($viewPath)) {
        throw new \Exception("View '{$view}' não encontrada");
    }
    
    include $viewPath;
}
```

---

## 💡 Casos de Uso

### Caso 1: Testar Nova Funcionalidade

```bash
# 1. Criar versão de teste
cp src/Views/app/dashboard-gold.php src/Views/dev/app/dashboard-gold.php

# 2. Modificar versão de teste (adicionar nova funcionalidade)
# ... editar arquivo ...

# 3. Testar em: /dev/app/dashboard/gold
# 4. Se funcionar, aplicar em produção
```

### Caso 2: Testar Novo Design

```bash
# 1. Criar versão de teste com novo CSS/HTML
# 2. Testar visualmente em /dev/app/dashboard/gold
# 3. Ajustar até ficar perfeito
# 4. Aplicar em produção quando estiver pronto
```

### Caso 3: Debugging

```bash
# 1. Criar versão de teste com logs adicionais
# 2. Reproduzir problema em /dev/app/dashboard/gold
# 3. Analisar logs
# 4. Corrigir e testar
# 5. Aplicar correção em produção
```

---

## 🔍 Como Verificar se Está em Ambiente de Teste

### Em PHP (views/controllers):

```php
<?php
if (is_dev_environment()) {
    echo "Você está em ambiente de TESTE";
} else {
    echo "Você está em ambiente de PRODUÇÃO";
}
?>
```

### Em JavaScript (se necessário no futuro):

```javascript
// Verificar header HTTP
if (document.querySelector('meta[name="dev-environment"]')) {
    console.log('Ambiente de TESTE');
} else {
    console.log('Ambiente de PRODUÇÃO');
}
```

---

## 📋 Checklist para IAs

Quando precisar criar uma versão de teste:

- [ ] 1. Criar pasta `src/Views/dev/[caminho]` se não existir
- [ ] 2. Copiar arquivo de produção como base
- [ ] 3. Adicionar banner de teste visível
- [ ] 4. Modificar título da página com [TESTE]
- [ ] 5. Fazer alterações necessárias
- [ ] 6. Testar acessando `/dev/[caminho-original]`
- [ ] 7. Verificar se banner aparece
- [ ] 8. Verificar se funcionalidade está OK
- [ ] 9. Quando aprovado, aplicar em produção

---

## ⚠️ Avisos Importantes

### ✅ FAÇA:
- ✅ Use `/dev/` para testar modificações
- ✅ Adicione sempre um banner visual indicando teste
- ✅ Teste completamente antes de aplicar em produção
- ✅ Delete versões de teste após aplicar em produção (opcional)

### ❌ NÃO FAÇA:
- ❌ NÃO teste funcionalidades críticas direto em produção
- ❌ NÃO deixe versões de teste sem banner visual
- ❌ NÃO se esqueça de que `/dev/` ainda usa o mesmo banco de dados
- ❌ NÃO assuma que mudanças em `/dev/` afetam produção

---

## 🎯 Exemplo Completo

### Criar e testar nova versão do Dashboard de Ouro:

```bash
# 1. Criar estrutura
mkdir -p "src/Views/dev/app"

# 2. Copiar base
cp "src/Views/app/dashboard-gold.php" "src/Views/dev/app/dashboard-gold.php"

# 3. Editar versão de teste
# Adicionar banner, modificar título, fazer alterações...

# 4. Fazer commit
git add src/Views/dev/app/dashboard-gold.php
git commit -m "TEST: Nova versão do dashboard de ouro"
git push

# 5. Deploy
ssh servidor "cd projeto && git pull"

# 6. Testar
# Produção: https://terminal.operebem.com.br/app/dashboard/gold
# Teste:    https://terminal.operebem.com.br/dev/app/dashboard/gold

# 7. Se aprovar, aplicar em produção
cp "src/Views/dev/app/dashboard-gold.php" "src/Views/app/dashboard-gold.php"
git add src/Views/app/dashboard-gold.php
git commit -m "PROD: Aplicando nova versão do dashboard de ouro"
git push
```

---

## 📊 Fluxograma de Decisão

```
Usuário acessa URL
        │
        ▼
    Começa com /dev/?
        │
    ┌───┴───┐
  SIM      NÃO
    │       │
    │       └──▶ Usa PRODUÇÃO
    │
    ▼
Existe versão em /dev/?
    │
┌───┴───┐
SIM    NÃO
  │      │
  │      └──▶ Usa PRODUÇÃO (fallback)
  │
  └──▶ Usa TESTE
```

---

## 🔧 Troubleshooting

### Problema: URL /dev/ mostra erro 404
**Solução**: Verificar se o sistema de detecção está ativo em `public/index.php`

### Problema: Sempre mostra versão de produção
**Solução**: Verificar se arquivo existe em `src/Views/dev/[caminho]`

### Problema: Mudanças em /dev/ afetam produção  
**Solução**: Isso NÃO deve acontecer. Verificar logs de erro.

---

## 📝 Resumo

Este sistema permite:
- ✅ Testar mudanças sem afetar produção
- ✅ Fallback automático para produção se teste não existir
- ✅ Fácil de usar: apenas adicione `/dev/` na URL
- ✅ Seguro: produção nunca é afetada

**URL de produção**: `/app/dashboard/gold`  
**URL de teste**: `/dev/app/dashboard/gold`

**Simples assim!** 🚀
