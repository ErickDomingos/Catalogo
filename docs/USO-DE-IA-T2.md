# Declaração de uso de IA generativa (Trabalho Individual II)


**Ferramenta:** Claude (Anthropic) e ChatGpt (Open AI).

**Para que foi usada**
- Geração do código-base do backend em PHP (núcleo, repositórios, serviços, controladores), do `schema.sql`, do seed, dos testes e das telas do front que consomem a API.
- Correção de bugs em scripts.
- Rascunho da documentação (API, segurança, testes).

**Como foi usada criticamente**
- Executei os 32 testes unitários e o script de segurança no meu computador e anexei as saídas reais em `docs/evidencias/`.
- A execução revelou problemas que corrigi usando o auxilio das Ias: um bug no cliente do script de segurança (o token CSRF era buscado depois do cookie; correção no commit `fix(test)`), valores duplicados no `.env` e a tela de administração que ficou fora de um commit (adicionada em seguida).
- Adaptei o ambiente à minha máquina (PHP 8.5 e MariaDB 10.11 no XAMPP) e conferi que nenhuma credencial ficou no repositório.
- Revisei o código de cada fase antes de commitar e encontrei alguns erros, usei as Ias para arrumar.
