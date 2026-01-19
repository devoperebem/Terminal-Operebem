# Script para fazer git pull no servidor de produção
$sshCommand = "cd domains/terminal.operebem.com.br/public_html/ && git pull origin main"

# Configurações do servidor
$server = "46.202.145.197"
$port = "65002"
$user = "u757800983"
$password = "nDQeyOyUpWcd8kki6D1Q*"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " DEPLOY - Git Pull em Produção" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Servidor: $server" -ForegroundColor Yellow
Write-Host "Comando: $sshCommand" -ForegroundColor Yellow
Write-Host ""
Write-Host "Conectando ao servidor via SSH..." -ForegroundColor Green

# Usando sshpass para autenticação automática
$env:SSHPASS = $password
ssh -o StrictHostKeyChecking=no -p $port "$user@$server" $sshCommand

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " Deploy concluído!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximo passo: Executar migration 027 no painel admin" -ForegroundColor Yellow
Write-Host "URL: https://operebem.com/secure/adm/migrations" -ForegroundColor Cyan
