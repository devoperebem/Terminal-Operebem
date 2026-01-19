# Script para rodar update_subscription_plans.php no servidor
$server = "46.202.145.197"
$port = "65002"
$user = "u757800983"
$sshCommand = "cd domains/terminal.operebem.com.br/public_html/ && php update_subscription_plans.php"

Write-Host "Executando update_subscription_plans.php no servidor..." -ForegroundColor Yellow

ssh -o StrictHostKeyChecking=no -p $port "$user@$server" $sshCommand
