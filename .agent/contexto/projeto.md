# Projeto

- Nome: Terminal Operebem.
- Tipo: aplicacao web PHP (MVC), roteador proprio, views server-side.
- Objetivo da task atual: habilitar o Terminal como origem de dados de pricing e materiais para o Portal do Aluno.

## Componentes relevantes para a task

- Rotas principais: `routes/web.php`
- Admin do Portal no Terminal: `src/Controllers/AdminAlunoController.php`
- Gestao de cursos/aulas/acessos no DB `aluno`: `src/Controllers/AdminAluno*Controller.php`
- Documentacao SSO atual: `docs/SSO_DOCUMENTATION.md`

## Restricoes de escopo

- Nao implementar auth proprio no Portal.
- Nao mover pagamentos para Portal.
- Nao alterar comportamento funcional do SSO vigente.
