# Script para conectar ao servidor e executar comandos
$sshCommand = @"
cd domains/terminal.operebem.com.br/public_html/ && git pull && php update_subscription_plans.php
"@

# Usar plink (PuTTY) ou ssh nativo
$server = "46.202.145.197"
$port = "65002"
$user = "u757800983"
$password = "nDQeyOyUpWcd8kki6D1Q*"

# Tentar com ssh nativo primeiro
Write-Host "Conectando ao servidor via SSH..."
$env:SSHPASS = $password
sshpass -e ssh -o StrictHostKeyChecking=no -p $port "$user@$server" $sshCommand
