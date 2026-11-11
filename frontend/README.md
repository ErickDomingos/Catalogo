# GameShelf — frontend

Interface web do GameShelf: HTML5, CSS3 e JavaScript sem frameworks. Começou como o Trabalho Individual I (catálogo estático de 128 jogos) e, no Trabalho 2, passou a conversar com a API PHP em `../backend`.

## Como executar

O frontend é servido pelo servidor do projeto, que também atende `/api/*`. Siga o `README.md` da raiz e abra http://localhost:8000.

Abrir `index.html` direto do disco (`file://`) ainda mostra o catálogo para consulta, mas sem login, cadastro, listas ou contagens do banco, porque dependem da API.

## Páginas

| Página | Função |
|---|---|
| `index.html` | Apresentação, destaques e resumo do catálogo (contagem vinda do banco) |
| `catalogo.html` | 128 fichas, busca, filtros e ordenação; com login, cadastro de jogos e listas pessoais |
| `conta.html` | Minha conta: CRUD de jogos e listas (administrador também gerencia o catálogo oficial) |
| `entrar.html` | Login e cadastro |
| `admin.html` | Gestão de usuários (somente administrador) |
| `sugestoes.html` | Demonstração do formulário de sugestão, sem envio |

## Funcionalidades

- Busca por título e estúdio (sem diferenciar maiúsculas e acentos); filtros por gênero, plataforma, console, duração e lista; ordenação por título, lançamento, nota ou duração.
- Com login: cadastrar jogos próprios, excluí-los e marcar Quero jogar, Jogando, Já joguei ou Zerado. Tudo é gravado no banco e aparece também em Minha conta.
- Sem login: catálogo somente leitura.
- Jogos oficiais excluídos pelo administrador somem do catálogo; só o administrador exclui jogos oficiais.
- Validação de campos com mensagem associada e foco no primeiro erro; o backend repete a validação.
- Acessibilidade: HTML semântico, ARIA, foco visível por teclado e respeito a `prefers-reduced-motion`.

## Organização

| Caminho | Responsabilidade |
|---|---|
| `css/estilo.css`, `css/conta.css` | Paleta, tipografia, componentes e responsividade |
| `js/api.js` | Cliente da API (sessão e token CSRF) |
| `js/acervo.js` | Catálogo ligado à API: jogos pessoais, listas e fichas excluídas |
| `js/catalogo.js` | Busca, filtros e ordenação |
| `js/conta.js`, `js/admin.js`, `js/entrar.js` | Telas de conta, administração e login |
| `js/totais.js` | Resumo da página inicial |
| `js/validacao.js`, `js/formulario.js` | Validação de formulários |
| `img/capas/` | Capas dos 128 jogos |
| `docs/FONTES.md` | Fontes e créditos (Trabalho 1) |
