# GameShelf

Catálogo acadêmico com 128 jogos conhecidos, desenvolvido com HTML5, CSS3
e JavaScript para o Trabalho Individual I de SCOM. Aluno responsável: Erick
Domingos.

## Executar

Descompacte a pasta e abra `index.html` diretamente em um navegador moderno.
Todos os recursos do site são locais e usam caminhos relativos. Não há build,
framework, instalação ou servidor obrigatório. Para incorporar esta versão
à sua pasta existente.


## Funcionalidades

- Navegação entre início, catálogo e demonstração de sugestão.
- 128 fichas estáticas disponíveis mesmo sem JavaScript.
- Busca por título e estúdio, ignorando maiúsculas e acentos.
- Filtros combinados por gênero, família de plataforma, console da edição, duração e lista pessoal.
- Duração: menos de 10 h, de 10 a 30 h inclusive, ou mais de 30 h; usa o ponto médio da faixa estimada.
- Ordenação por título, lançamento, nota ou duração; valores desconhecidos no fim.
- Adicionar jogos próprios com desenvolvedora, ano, plataformas, duração e notas opcionais.
- Remover qualquer título, inclusive os 128 iniciais; desfazer a última remoção.
- Restaurar todos os jogos iniciais preservando os cadastros pessoais.
- Listas: Quero jogar, Jogando, Já joguei, Zerado e Sem lista; um estado por jogo.
- Painel com contagens e atalhos, incluindo títulos cadastrados pelo visitante.
- Notas pesquisadas com plataforma, edição e fonte visíveis.
- Validação de campos com mensagem associada e foco no primeiro erro.
- Confirmação explícita de sugestão simulada, sem envio nem armazenamento.

## Organização

| Caminho | Responsabilidade |
|---|---|
| `index.html` | Apresentação, destaques e critérios do acervo |
| `catalogo.html` | Filtros, 128 fichas e formulário do acervo pessoal |
| `sugestoes.html` | Demonstração do formulário de sugestão |
| `css/estilo.css` | Paleta, tipografia, componentes e responsividade |
| `js/validacao.js` | Validação compartilhada e mensagens por campo |
| `js/meus-jogos.js` | Cadastro, capas, armazenamento e remoção |
| `js/listas.js` | Estados pessoais, persistência, seletores e contagens |
| `js/catalogo.js` | Busca, filtros, ordenação e contagem |
| `js/formulario.js` | Validação e simulação da sugestão |
| `img/` | Logo SVG e 128 artes promocionais JPEG locais |
| `dados/jogos.json` | Registro dos metadados e fontes (não carregado em tempo de execução) |
| `docs/` |  Fontes

O HTML carrega scripts clássicos com `defer`. Não usa módulos, `fetch`, CDN
ou fontes externas. A validação compartilhada é carregada antes dos formulários.
O acervo comunica alterações pelo evento `gameshelf:acervo-alterado`. As listas usam
`gameshelf:lista-alterada`; ambos fazem o catálogo reaplicar os filtros.

## Dados e limites

As notas da crítica são Metascores pesquisados no Steam e em fontes secundárias,
sempre com plataforma e edição identificadas. As fichas também mostram, quando
confirmado, o percentual de avaliações positivas em inglês no Steam. Fontes,
critérios e créditos estão em `docs/FONTES.md`. As notas são um retrato da consulta
de 20–21/09/2026, sem atualização automática. Notas inseridas pelo visitante são
identificadas como fornecidas por ele, sem verificação editorial.

As durações são faixas editoriais aproximadas, não médias pesquisadas no
HowLongToBeat. Campanhas e partidas abertas são diferenciadas. As capas locais
são artes promocionais de terceiros, com origem documentada. A interface retoma
roxo escuro, dourado e magenta, com fichas detalhadas e imagens reconhecíveis.

O acervo tenta usar `localStorage`. Os estados pessoais ficam em `gameshelf:listas:v1`.
Remover um título o exclui das contagens, mas mantém sua marcação para a restauração.
Não há conta, backup em arquivo ou sincronização. Limpar os dados do navegador apaga as listas. Remoções de jogos iniciais são guardadas em
`gameshelf:removidos`. O botão de restauração não apaga cadastros pessoais.
Desfazer recupera apenas a última remoção desta página; não é um histórico permanente.
Os destaques da página inicial são uma seleção editorial fixa; as remoções personalizam a página Catálogo.
 A chave técnica `pixelteca:meus-jogos` foi
mantida para preservar os cadastros da versão anterior na mesma origem. Se ler ou
salvar falhar, avisa e mantém a lista em memória durante a página. Ao abrir por
`file://`, a persistência varia conforme o navegador; mover a pasta também pode
mudar o armazenamento acessível. Isso não impede consultar as fichas e testar
as interações. Os dados não sincronizam entre dispositivos ou navegadores.
Referência: [MDN sobre localStorage](https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage).
