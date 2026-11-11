# Declaração de uso de IA generativa (Trabalho Individual II)

**Ferramentas:** Claude (Anthropic), em mais de uma conta e sessão, e ChatGPT (OpenAI).

## Para que foi usada
- Geração do código-base do backend em PHP (núcleo, repositórios, serviços, controladores), do `schema.sql`, do seed, dos testes e das telas do front que consomem a API.
- Correção de bugs, incluindo a sincronização entre a página do catálogo e a página Minha conta (`js/acervo.js`) e a contagem de títulos da página inicial (`js/totais.js`).
- Revisão geral do projeto antes da entrega: higiene do repositório, conferência dos requisitos e achados da revisão.
- Rascunho da documentação (README, arquitetura, API, segurança, testes, checklist) e do relatório técnico.

## Como foi usada criticamente
- Executei os 32 testes unitários e o script de segurança no meu computador e anexei as saídas reais em `docs/evidencias/`.
- A execução revelou problemas que corrigi com ajuda das IAs: um bug no cliente do script de segurança (o token CSRF era buscado depois do cookie; commit `fix(test)`), valores duplicados no `.env` e a tela de administração que ficou fora de um commit.
- Ao testar no navegador, encontrei que jogos e listas cadastrados em Minha conta não apareciam no catálogo e vice-versa, porque o catálogo ainda guardava os dados no `localStorage` (código do Trabalho 1). Uma primeira solução da IA (um script de sincronização) não resolveu os jogos pessoais; testei, relatei o resultado e a correção final passou a gravar tudo no banco pela API. Também encontrei que jogos oficiais excluídos pelo administrador continuavam no catálogo e que a contagem da página inicial não atualizava, e ambos foram corrigidos.
- Aceitei uma decisão de projeto proposta pela IA e revi o impacto: a remoção de jogos oficiais por usuário comum, que no Trabalho 1 ficava só no navegador, foi retirada; só o administrador exclui jogos oficiais.
- Adaptei o ambiente à minha máquina (PHP 8.5 e MariaDB 10.11 no XAMPP) e conferi que nenhuma credencial ficou no repositório nem no histórico do Git.
- Revisei o código de cada fase antes de commitar; os erros que encontrei foram corrigidos com apoio das IAs. As IAs não executaram o PHP: toda validação foi feita por mim, nos testes automáticos e no navegador.
