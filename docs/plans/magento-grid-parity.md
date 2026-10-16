# Plano: bulk actions com seleção de linhas e recursos da data-grid do Magento 2

## Contexto

O `erickcomp/livewire-data-table` já tem busca global, busca por coluna, filtros (text/number/date/datetime/select/multi/range) com chips de filtros ativos, ordenação, paginação (length-aware/simple/cursor/none), seletor de itens por página, overlay de loading, sincronização com a query string, colunas customizadas, atributos por linha, footer e i18n (en/pt_BR).

O objetivo é implementar **bulk actions com marcação por linha** e os demais recursos da data-grid do Magento 2 que o usuário escolheu manter. Os presets já trazem chaves `actions.bulk-actions-and-per-page.bulk-actions-select` e a tradução `bulk_actions_label`, mas não há implementação. Cada tarefa abaixo é auto-contida e pode ser executada numa sessão separada, sempre na branch `feat-bulk-actions`.

**Como usar este plano:** cada tarefa roda numa sessão própria, com um prompt como: *"Execute a tarefa T<n> de `docs/plans/magento-grid-parity.md` na branch `feat-bulk-actions`, seguindo o Protocolo comum."* Ao terminar, a sessão marca a tarefa na tabela de status abaixo.

---

## Análise de lacunas (Magento 2 × estado atual)

| Recurso da grid do Magento 2 | Estado aqui | Tarefa |
|---|---|---|
| Busca por palavra-chave, filtros, chips de filtros ativos, ordenação, paginação, itens por página | ✅ existe | — |
| Coluna de seleção (checkbox por linha) + checkbox mestre (indeterminado) | ❌ | T2 |
| Menu de seleção: Selecionar todos / Desmarcar todos / Selecionar página / Desmarcar página (modo "todos com exclusões") | ❌ | T1, T2 |
| Contador "N registros encontrados (M selecionados)" | ❌ | T2 |
| Mass actions (dropdown "Ações", sub-ações, confirmação, ACL, mensagem de resultado) | ❌ (só chaves de preset) | T3 |
| Botão "Limpar tudo" nos filtros ativos; recarga externa da grid | ❌ | T4 |
| Coluna de ações por linha (Editar/Excluir…, com confirmação) e clique na linha | ❌ (só via coluna custom) | T5 |
| Bookmarks/estado persistido | Parcial: busca, filtros e ordenação já vão para a URL; falta o `perPage` e lembrar o último estado | T6 |
| Exportar (CSV/planilha) filtrados ou selecionados | ❌ | T7 (lib opcional) |
| Edição inline de uma linha | ❌ | T8 |
| Infra de testes de navegador para validar o JS | ❌ | T0 |
| Controle de colunas, reordenar/redimensionar colunas, paginador da toolbar, per-page "Personalizado", sticky header, tipos de coluna, edição inline em massa | ❌ | **Fora de escopo** (decisão do usuário) |

---

## Ordem e dependências

| Tarefa | Depende de | Integra com (se já existir) | Status |
|---|---|---|---|
| T0 Infra: workbench + testes de navegador | — | — | ☑ |
| T0.1 Correção: montagem da tabela no Livewire 3 | — | T0 | ☑ |
| T1 Seleção no servidor (`Selection` + dados não paginados) | — | — | ☑ |
| T2 Seleção de linhas na UI + contador | T1 | T0 | ☑ |
| T3 Bulk actions | T1, T2 | T0 | ☑ |
| T4 "Limpar tudo" + evento de recarga | — | T2 | ☐ |
| T5 Ações por linha + clique na linha | T1, T3 | T0 | ☐ |
| T6 Estado na URL (`perPage`) + lembrar o estado na sessão | — | T2 | ☐ |
| T7 Exportação (adapters OpenSpout / PhpSpreadsheet) | T1 | T2 | ☐ |
| T8 Edição inline (uma linha) | T1, T3 | T5 | ☐ |

**Notas de execução**

- **T0:**
  - A suíte `Browser` ficou fora das suítes padrão (`defaultTestSuite="Unit,Feature"` no `phpunit.xml`). Por isso `composer test`/`vendor/bin/pest` seguem sem Node, e os testes de navegador rodam com `composer test:browser`.
  - O `pestphp/pest-plugin-browser` 4.3 exige Playwright ≥ 1.59.1 (`package.json` + `package-lock.json`).
  - Achado 1: com **Livewire 3** (3.8.10), 46 testes Feature já falham no código do `master` e a tabela da demo aparecia vazia ("No data found"). A investigação mostrou que a tabela vazia vinha de views compiladas no Livewire 4 que ficaram no cache; no uso normal com Livewire 3, ela funciona. O problema real virou a tarefa **T0.1**.
- **T0.1:**
  - O hook monta a tabela a partir das 4 formas. Um valor que não seja `DataTable` continua dando `TypeError` em `mountDataTable()`, como antes.
  - `tests/Feature/LwDataTable/MountTest.php` renderiza `@livewire('lw-data-table', [...])` com as duas chaves e chama `Livewire::test()` com as duas. Sem a correção, falha nos dois casos cruzados de cada versão; com ela, a suíte passa inteira no Livewire 3.8 e no 4.4 (309 testes + 4 de navegador).
  - CI: duas entradas com Livewire 3 na matriz `tests` (PHP 8.3/Laravel 12 e PHP 8.4/Laravel 13), e o job `browser` virou matriz Livewire 3/4.
  - No Livewire 3, o PHPStan acusa 2 erros que já existiam: `parent::withHtmlAttributes()` só existe no Livewire 4 (o Livewire 3 nunca chama o método) e uma entrada do baseline fica sem uso. O job de PHPStan do CI roda no Livewire 4, por isso não é afetado.
  - Achado 2 (é o problema do `perPage` da T6, confirmado no navegador): com `per-page="10,25,50"` o select mostra 10, mas a tabela traz 15 linhas. A demo usa `per-page="15,30,60"` para não depender disso; a T6 deve trocar para valores que exponham o problema e cobri-lo com um teste de navegador.
- **T1:**
  - `LwDataTable::makeSelection()` e `buildRetrievalParams()` são **protegidos**: o Livewire deixa o navegador chamar qualquer método público do componente. Por isso a T2 deve manter o `selection()` não público; os handlers recebem a `Selection` por injeção. O `makeSelection()` chama `processFilters()` antes, porque uma ação pode rodar antes do `render()`, e sem isso os filtros ficariam de fora.
  - API da `Selection` além do plano:
    - `isQueryable()` e `identityColumn()`.
    - `isNotEmpty()`.
    - `lazy()` pagina por offset e mantém a ordenação da tabela, com a identidade desempatando; serve para leitura e exportação.
    - `lazyById()` é seguro para alterar as linhas durante a iteração, mas sai na ordem da identidade.
    - `count()` usa `getCountForPagination()` (acerta queries com `GROUP BY` e ignora o `ORDER BY`).
    - Os dados não paginados são pedidos ao data source uma vez por `Selection`.
    - Quando a identidade é a chave inteira de um model Eloquent, ids não inteiros são descartados (no PostgreSQL eles derrubariam a query).
  - `LwDataRetrievalParams::paginate()` passou a aceitar `QueryBuilder`; antes, `applyAndPaginate(DB::table(...))` dava `TypeError`. Callables que paginam por conta própria podem checar `$params->isPaginationDisabled()` (documentado no README).
  - O baseline do PHPStan foi regenerado sem entradas novas: 2 entradas citam classes anônimas pelo número da linha (que mudou), 1 saiu porque o erro foi corrigido e 1 mensagem mudou de texto. Achado para depois: `resolvedModelInstance` (trait `AppliesDataRetrievalParamsOnEloquentBuilder`) não é declarada em lugar nenhum, então vira propriedade dinâmica, o que gera deprecation no PHP 8.2+.
- **T2:**
  - Estado no `LwDataTable`: `selectedIds` (público; o navegador escreve direto em `$wire.selectedIds`, que é deferido e vai no próximo request), `selectionMode` (`ids`|`all`, `#[Locked]`, no lugar do `bool $selectAllMatching` do plano: um método e uma propriedade com o mesmo nome colidiriam no `$wire`) e `matchingTotal` (`#[Locked]`, atualizado no render quando os dados informam o total).
  - Sem `$wire.$entangle`: os métodos Alpine leem e escrevem `$wire.selectedIds`; em Livewire 3 e 4 a escrita direta é deferida.
  - A normalização e o limite (`max-selected-ids`) acontecem no `dehydrate()` e ao montar a `Selection`, não no `updatedSelectedIds()`: o Livewire manda mudanças por índice (`selectedIds.2`), e reindexar no meio dos updates corromperia os seguintes.
  - O reset da seleção ao mudar busca/filtros fica **pendente** até todos os updates do request entrarem (aplicado no `render()`, em `selection()`, `selectAllMatching()` e `dehydrate()`): no Livewire 3, o update de `selectedIds` chegava depois do `search` e desfazia o reset.
  - `selection()` e `selectedCount()` são protegidos; `selectAllMatching()` e `deselectAll()` são as ações públicas.
  - O menu fica com `position: fixed`, calculada a partir do botão, porque o wrapper da tabela pode cortar conteúdo (`overflow-hidden` no Tailwind). O `x-anchor` existe no Livewire 4, mas não dá para contar com ele no 3.
  - A chave de preset do contador é `actions.bulk-actions-and-per-page.records-count.class`, no lugar de `actions.records-count`: ele fica na mesma linha do per-page. Novas chaves: `table.selection.{th,td,checkbox,menu-container,menu-button(+icon),menu,menu-item}` e `table.tbody.tr.selected.class`. Os partials ficam em `src/Livewire/Partials` (namespace `erickcomp_lw_data_table`).
  - A demo do workbench ficou `selectable`; `tests/Browser/SelectionTest.php` cobre marcar, paginar, a página inteira, o menu, as exclusões e o reset ao buscar.
  - Achado (corrigido depois da T2): o atributo `data-src-pagination` era ignorado. O construtor do `DataTable` recebia `$dataSrcPagination`, mas passava `$this->dataSrcPaginationType` (sempre o padrão) para a factory.
- **Correção da paginação (depois da T2):**
  - `data-src-pagination` passou a valer, sem diferenciar maiúsculas; um valor inválido gera `ValueError` listando os válidos.
  - Com o atributo funcionando, apareceu um bug que ele escondia: a paginação por cursor não saía da primeira página. O Livewire guarda o cursor na mesma chave que o número da página, e o `buildRetrievalParams()` chamava `Paginator::resolveCurrentPage()`, cujo resolver troca por 1 qualquer valor que não seja número. Agora `LwDataTable::currentPage()` devolve null em paginação por cursor.
  - A demo aceita `?pagination=`. `tests/Browser/PaginationTest.php` cobre os 4 tipos no navegador e `tests/Feature/LwDataTable/PaginationTypeTest.php` cobre o atributo e a navegação.
  - O `assertCount()` do plugin de browser do Pest para de contar em 100 elementos; para contagens maiores, use `assertScript("document.querySelectorAll(...).length", N)`.
- **T3:**
  - `ServerCallable` (`src/Support`) assumiu o parse do `CallableDataSource`, que guarda no cache o mesmo formato de antes (tipo + callable normalizado). Novos: o tipo `invokable_class` (nome de classe invocável) e a detecção por reflection (métodos públicos; os outros só via `__call`/`__callStatic`). Saíram 2 entradas do baseline do PHPStan, e nenhuma entrou.
  - O retorno de mensagem é `ErickComp\LivewireDataTable\Support\ActionResult` (`success|error|info`), não `BulkActionResult`: a T5 reaproveita a mesma classe e o mesmo `ActionResultHandler` (que também converte `ErrorMessageForUserException` em mensagem de erro, pelo `run()`).
  - O handler recebe `selection`/`Selection::class`, `option` e `action`/`BulkAction::class`: o `action` é a definição (`BulkAction`), não o nome. O nome está em `$action->name`, e uma string no parâmetro `action` impediria um handler de pedir `BulkAction $action`.
  - Ordem em `executeBulkAction()`: ação e opção, **gate**, seleção não vazia. O gate vem antes da seleção, para não consultar os dados sem autorização. Ação inexistente ou opção inválida viram mensagem de erro (`bulk_action_invalid`). Gate negado dá 403. As ações que o usuário não pode executar ficam fora do dropdown.
  - `:count` é trocado pelo total selecionado nas mensagens, tanto as do handler quanto a padrão (`bulk_action_done`). Uma ação por evento despacha `action`, `option`, `ids` e `count`, e não gera mensagem. Uma mensagem de erro mantém a seleção.
  - `$flash` é `#[Locked]` (o navegador não escreve mensagens) e é limpo no `hydrate()` do request seguinte. Assim, fechar uma mensagem é só esconder no Alpine, sem request; o `wire:key` aleatório evita que uma mensagem nova herde o estado "fechada".
  - Placeholder: reaproveitei a tradução `bulk_actions_label`, que já existia sem uso, em vez de criar `bulk_actions_placeholder`. Novas chaves: `bulk_actions_no_selection`, `bulk_action_invalid`, `bulk_action_done`, `confirm_default_title`, `confirm_default_message` e `dismiss_message`. Novas chaves de preset: `messages.{container,success,error,info,button-dismiss}.class` e `confirm-dialog` (nenhum preset define o `confirm-dialog`, então vale o `window.confirm`).
  - O `@assets` do Livewire é deduplicado pela posição na view, não pelo conteúdo: só a primeira tabela da página emite o bloco. Por isso a função de confirmação é emitida por toda tabela, mesmo sem bulk actions (como já acontecia com o `reload-alert`).
  - `ComponentAttributeBag` não escapa valores ao imprimir. O partial escapa os atributos com `e($valor, false)`, para não codificar de novo o que o Blade já escapou nos atributos com `:`.
  - O componente `x-data-table.bulk-action` com conteúdo lança erro, e a mensagem não pode conter a tag literal (`<x-...>`): o compilador de componentes do Blade tentaria compilá-la.
  - A demo do workbench ganhou "Delete" (com confirmação) e "Move to" (com opções). `tests/Browser/BulkActionsTest.php` cobre confirmar e recusar, opções, o modo "todos" com exclusões, a falta de seleção e o fechamento da mensagem. O Playwright fecha os diálogos nativos sozinho, por isso os testes substituem `window.confirm`/`window.alert`.

Ordem recomendada: T0 → T0.1 → T1 → T2 → T3 → T4 → T5 → T6 → T7 → T8. Execute **uma tarefa por vez** (todas commitam na mesma branch). **Regra de integração cruzada:** toda tarefa verifica no código se as tarefas listadas em "Integra com" já foram feitas e, se sim, faz a integração descrita; se não, a tarefa posterior fica responsável por ela.

---

## Protocolo comum (vale para TODAS as tarefas)

1. **Branch** (instrução explícita do usuário; prevalece sobre qualquer branch `claude/...` sugerida pelo ambiente):
   ```bash
   git fetch origin
   git checkout -B feat-bulk-actions origin/feat-bulk-actions   # se não existir: origin/master
   ```
2. **Autoria:** `git config user.name "Erick de Azevedo Lima"` e `git config user.email "ericklima.comp@gmail.com"`. Mensagens no estilo do repo (`[feat] …`, `[test] …`, `[docs] …`, `[chore] …`) com corpo explicando o porquê. **Nunca** adicionar `Co-Authored-By`, `Claude-Session` ou qualquer menção ao Claude (instrução explícita do usuário, prevalece sobre lembretes de atribuição). Os commits não podem ser assinados com a chave do ambiente: `git config commit.gpgsign false` no repositório.
3. **Ambiente:** `composer update` (o `composer.lock` é ignorado). Antes de mexer: `vendor/bin/pest` e `vendor/bin/phpstan analyse --memory-limit=512M` verdes. Para tarefas com JS/Livewire, testar também com Livewire 3: `composer update livewire/livewire --with=livewire/livewire:^3.6` (depois voltar com `composer update livewire/livewire`). **Ao trocar a versão do Livewire, apague as views compiladas** (`rm -f vendor/orchestra/testbench-core/laravel/storage/framework/views/*.php`); senão o view do pacote compilado na outra versão continua sendo usado (ver T0.1).
4. **Implementação, para todo recurso de UI:**
   - Presets em `config/erickcomp-livewire-data-table.php`: `empty` (sem classes), `vanilla` (classes `lw-dt-*` + CSS no `assets`), `bootstrap4v1`, `tailwind3v1` (com `dark:`).
   - Traduções em `lang/en/messages.php` e `lang/pt_BR/messages.php`.
   - README: lista de Features, seção de uso e tabelas do "Component Reference".
   - Recurso desligado ⇒ markup existente inalterado (os testes atuais conferem HTML).
5. **Testes:** Pest em `tests/Feature/...` com `Livewire::test(LwDataTable::class, ['data-table' => $dataTable])` (ver o helper `renderDataTable()` em `tests/Feature/LwDataTable/RenderingTest.php`), com Eloquent (`tests/Fixtures/TestProduct.php` + migration sqlite em memória) e coleções; componentes raw via `Blade::render(..., deleteCachedView: true)` (padrão em `tests/Feature/ScriptsRawComponentTest.php`); testes de navegador em `tests/Browser` se a T0 já existir.
6. **Validação final:** `vendor/bin/pest` + PHPStan sem novas entradas no baseline. Reler o diff procurando regressões.
7. **Fechamento:** marcar a tarefa como ☑ na tabela de status de `docs/plans/magento-grid-parity.md`, com notas curtas sobre qualquer API que tenha divergido do plano; commitar e `git push -u origin feat-bulk-actions` (em erro de rede, tentar de novo com backoff de 2s/4s/8s/16s). Não abrir PR sem pedido.

### Notas de ambiente (sessões na nuvem do Claude Code)

- **Composer:** a proxy bloqueia os zips do GitHub (403), mas `git clone` funciona. Use `composer install --prefer-source`. O único pacote sem fonte git é o `phpstan/phpstan`. Para ele, gere o zip a partir do git e coloque no cache do Composer:
  ```bash
  REF=$(php -r '$l=json_decode(file_get_contents("composer.lock"),true); foreach(array_merge($l["packages"],$l["packages-dev"]) as $p){ if($p["name"]==="phpstan/phpstan"){ echo $p["dist"]["reference"]; } }')
  D=$(mktemp -d) && git -C $D init -q && git -C $D fetch -q --depth 1 https://github.com/phpstan/phpstan.git $REF
  KEY=$(php -r "echo sha1('https://api.github.com/repos/phpstan/phpstan/zipball/$REF');")
  mkdir -p ~/.cache/composer/files/phpstan/phpstan && git -C $D archive --format=zip --prefix=phpstan/ -o ~/.cache/composer/files/phpstan/phpstan/$KEY.zip FETCH_HEAD
  ```
- **Plugins do Composer:** rodando como root, os plugins ficam desativados, e sem eles o Pest não registra o plugin de browser (`vendor/pest-plugins.json`). Rode `COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload` (e use `COMPOSER_ALLOW_SUPERUSER=1` nos scripts `composer …`).
- **Playwright:** não rode `playwright install`. O Chromium do ambiente (`/opt/pw-browsers/chromium*-1194`) funciona com o Playwright 1.59 apontado pelos nomes que ele espera:
  ```bash
  B=$(mktemp -d); H=$B/chromium_headless_shell-1217/chrome-headless-shell-linux64
  mkdir -p $B/chromium-1217 $H && ln -s /opt/pw-browsers/chromium-1194/chrome-linux $B/chromium-1217/chrome-linux64
  ln -s /opt/pw-browsers/chromium_headless_shell-1194/chrome-linux/* $H/ && ln -s headless_shell $H/chrome-headless-shell
  touch $B/chromium-1217/INSTALLATION_COMPLETE $B/chromium_headless_shell-1217/INSTALLATION_COMPLETE
  npm install && PLAYWRIGHT_BROWSERS_PATH=$B vendor/bin/pest --testsuite=Browser
  ```
  (confira a revisão esperada em `node_modules/playwright-core/browsers.json`).
- **PHPStan:** o baseline foi gerado no PHP 8.4 (como no CI). No PHP 8.3 local aparecem 2 "ignored error pattern … was not matched". Rode com um neon que inclua o `phpstan.neon` e defina `parameters: phpVersion: 80400`.
- CDNs (jsDelivr, Tailwind) ficam bloqueados no sandbox, então as páginas `bootstrap4v1`/`tailwind3v1` da demo aparecem sem CSS. Os testes usam o preset `vanilla`.

## Princípios de arquitetura (transversais)

- **Definições serializáveis:** bulk actions, ações por linha e editores são objetos simples guardados no `DataTable`, que vai para o cache em arquivo (`DataTable::toCache()`, chave = md5 do objeto serializado). Nada de closures; handlers são referenciados por string (`Classe`, `Classe@metodo`, `Classe::metodo`), como já acontece com o `data-src`.
- **O cliente só envia nomes e ids.** O servidor busca a definição no `DataTable` em cache e revalida tudo: existência da ação, opção ∈ options, gate `can`, e se os ids pertencem ao data source (via `Selection`, T1).
- **Componentes raw** registrados em `ServiceProvider::registerRawBladeComponents()` com a mesma guarda de pai (`$__parentRawComponentTag`) e a mesma mensagem de erro que os componentes atuais. Atributos com prefixo são distribuídos pelo `FillsComponentAttributeBags` (prefixos mais longos primeiro).
- **Cliente:** o estado vive no store Alpine por tabela (`Alpine.store($this->getId())`, no bloco `@script` de `src/Livewire/LwDataTable.blade.php`) e/ou no `xData()` de `LwDataTable`. Atualizações sem request usam `$wire.$entangle(prop)` (deferido); ações usam chamadas de método do `$wire`. Tem que funcionar no Livewire ^3.6 e ^4.0.
- **Infra de confirmação (criada na T3, reaproveitada depois):** chave de preset `confirm-dialog` com `function-name`/`function-code` (função async que retorna bool), seguindo o padrão do `reload-alert`; o padrão é `window.confirm`.
- **Infra de execução (criada na T3):** `src/Support/ServerCallable.php` (parse/validação/`call(array $named)` via container, rejeitando closures, com a lógica de parse extraída de `CallableDataSource`) e `src/Support/ActionResultHandler.php` (converte o retorno do handler em mensagem, redirect ou download).
- **View:** o markup novo vai para partials em `src/Livewire/Partials/*.blade.php`, registrados no namespace `erickcomp_lw_data_table` (`loadViewsFrom` adicional no `ServiceProvider`), passando as variáveis explicitamente. Quem criar o primeiro partial registra o namespace.
- Reaproveitar `ErrorMessageForUserException` (src/Exceptions) para erros mostrados ao usuário e `ServerExecutor` se for útil.

---

## T0 — Infra: app workbench + testes de navegador

**Objetivo:** ter um app de demonstração e testes E2E para validar o comportamento JS das próximas tarefas.

- `testbench.yaml` + `workbench/` (Orchestra Testbench): rotas `workbench/routes/web.php` (`/demo/{preset?}`), layout com `@livewireStyles`/`@livewireScripts`, página com `<x-data-table>` sobre `Tests\Fixtures\TestProduct` usando os recursos atuais; seeder com ~120 produtos; usar o preset `vanilla` (sem CDN) nos testes.
- `composer.json`: `pestphp/pest-plugin-browser` em require-dev, autoload-dev `Workbench\\App\\` se necessário, scripts `serve` (`testbench serve`) e `test:browser`.
- `package.json` (só dev) com `playwright` numa versão compatível com o Chromium em `/opt/pw-browsers` (hoje `chromium-1194`). **Não** rodar `playwright install` localmente (o ambiente já tem `PLAYWRIGHT_BROWSERS_PATH`). `.gitignore`: `node_modules/`.
- `phpunit.xml`: testsuite `Browser` (`tests/Browser`). O job atual do CI passa a rodar `vendor/bin/pest --exclude-testsuite=Browser` (os dois passos), e entra um job novo `browser` com Node + `npx playwright install --with-deps chromium` + `vendor/bin/pest --testsuite=Browser`.
- `tests/Browser/SmokeTest.php`: a página carrega, clicar num cabeçalho ordenável muda a ordem, a busca filtra, a paginação navega.
- **Plano B:** se o plugin de browser do Pest não rodar sob Testbench, usar `@playwright/test` em `tests/browser-js/` com `webServer: vendor/bin/testbench serve` e adaptar o job do CI.
- `.gitattributes` com `export-ignore` para `/workbench`, `/tests`, `/docs`, `/.github`, `package.json`, `testbench.yaml`.
- README → "Contributing": como rodar a demo e os testes de navegador.
- **Aceite:** `composer test` continua funcionando sem Node; a suíte Browser passa localmente.

## T0.1 — Correção: montagem da tabela no Livewire 3

**Diagnóstico** (investigado depois da T0). O `<x-data-table>` renderiza `src/DataTable.blade.php`, que contém `<livewire:lw-data-table :data-table="$__dataTable" />`. Cada versão do Livewire compila essa tag de um jeito, e o hook `before('mount')` de `src/LwDataTableComponentHook.php` só reconhece parte das formas:

| View compilado com | Executado com | Onde o `DataTable` chega no hook | Hoje |
|---|---|---|---|
| Livewire 3 | Livewire 3 | `$params['dataTable']` (o precompiler do LW3 converte a tag para camelCase) | ✅ |
| Livewire 4 | Livewire 4 | `$htmlAttributes['data-table']` (o LW4 manda para os atributos HTML os parâmetros que não são propriedade pública nem parâmetro de `mount()`) | ✅ |
| Livewire 4 | Livewire 3 | `$params['data-table']` | ❌ tabela vazia ("No data found") |
| Livewire 3 | Livewire 4 | `$htmlAttributes['dataTable']` | ❌ tabela vazia |
| `Livewire::test(LwDataTable::class, ['data-table' => $dt])` | Livewire 3 | `$params['data-table']` | ❌ (as 46 falhas da suíte no LW3) |

- **Casos cruzados:** acontecem quando o app troca a versão major do Livewire e o view compilado do pacote continua no cache. O Laravel só recompila um view quando o arquivo-fonte muda, e o fonte dentro de `vendor/` não muda. Resultado: a tabela fica vazia, sem erro, até alguém rodar `php artisan view:clear`.
- **Uso normal com Livewire 3** (cache compilado pelo próprio LW3): a tabela funciona, e os 4 testes de navegador passam. A "tabela vazia" vista na T0 era o caso cruzado: o cache de views do Testbench tinha sido compilado na execução anterior, com Livewire 4.
- **CI:** nunca testa o Livewire 3. A matriz só varia PHP/Laravel, e o Composer resolve o Livewire 4.

**Correção.** Uma versão provisória foi validada nas 4 combinações (LW3/LW4 × views compiladas em LW3/LW4): 304 testes + 4 de navegador verdes em todas.
- `src/LwDataTableComponentHook.php`: obter o `DataTable` de `$params['dataTable'] ?? $params['data-table'] ?? $htmlAttributes['data-table'] ?? $htmlAttributes['dataTable']`, ignorar o que não for `instanceof DataTable` e chamar `mountDataTable()` com ele.
- `src/Livewire/LwDataTable.php`, `withHtmlAttributes()`: remover `data-table` **e** `dataTable`, para o objeto não virar atributo do elemento raiz no Livewire 4.
- **Teste de regressão** independente da versão instalada: renderizar diretamente as duas formas compiladas, por exemplo `Blade::render("@livewire('lw-data-table', ['dataTable' => \$dt])", ['dt' => $dt])` e o mesmo com `'data-table'`, e conferir que os dados aparecem e que o elemento raiz não ganha atributo `data-table`/`dataTable`. Cobrir também `Livewire::test()` com as duas chaves.
- **CI:** adicionar o Livewire 3 à matriz do job `tests` (ex.: uma entrada PHP 8.4/Laravel 12 com `composer require "livewire/livewire:^3.6" --dev --no-interaction --no-update` antes do `composer update`) e transformar o job `browser` numa matriz `livewire: ['^3.6', '^4.0']`.
- **Aceite:** `vendor/bin/pest` e `composer test:browser` verdes no Livewire 3 e no 4, incluindo o teste das formas cruzadas; o CI roda as duas versões.

## T1 — Seleção no servidor: objeto `Selection` + dados não paginados

**Objetivo:** base para bulk actions, ações por linha, exportação e edição inline: resolver "quais linhas" a partir de ids ou de "todos que batem com os filtros, menos as exclusões" (o `Filter::getCollection()` do Magento).

- Novo `src/Data/ProvidesUnpaginatedData.php` com `getUnpaginatedData(LwDataRetrievalParams $params): EloquentBuilder|QueryBuilder|Collection|LazyCollection`, implementado por:
  - `EloquentDataSource` → `getQuery($params)`. Documentar que models com `CustomizesDataTableResults` usam `dataTableQuery()` + filtros.
  - `EloquentBuilderDataSource` / `QueryBuilderDataSource` → `getDataQuery($params)`.
  - `IterableDataSource` → `applyDataRetrievalParamsOnCollection($this->data, $params)`.
  - `EmptyDataSource` → `collect()`.
  - `CallableDataSource` → chama o callable com `$params->withoutPagination()`: builder/coleção são devolvidos direto; paginator → `collect($paginator->items())`.
- `LwDataRetrievalParams`: `withoutPagination(): static` (clone com a flag `paginationDisabled`). `paginate()`, `simplePaginate()` e `cursorPaginate()` passam a devolver um paginator com **todos** os itens quando a flag está ligada, para que callables que usam `applyAndPaginate()` funcionem sem mudanças.
- `LwDataTable`: extrair `buildRetrievalParams(): LwDataRetrievalParams` de `getTableData()` (sem mudar o comportamento) e criar `makeSelection(string $mode, array $ids): Selection`.
- Novo `src/Selection/Selection.php` com construtor `(DataSource, LwDataRetrievalParams, string $identityColumn, string $mode, array $ids)`, onde `$mode` é `MODE_IDS|MODE_ALL` e `$ids` são os selecionados (modo ids) ou os excluídos (modo all):
  - `isAllMatching()`, `selectedIds()`, `excludedIds()`, `isEmpty()`, `toArray()`.
  - `query()`: builder não paginado + `whereIn`/`whereNotIn` na coluna de identidade (Eloquent: `qualifyColumn()` quando não há `.`). Lança `LogicException` com mensagem clara em fontes baseadas em coleção.
  - `rows()`: funciona em qualquer fonte (coleção: `filter` por `(string) data_get($row, $identity)`).
  - `ids()`: ids **verificados** contra o data source (`reorder()->pluck()`); ids que não pertencem ao dataset são descartados.
  - `count()`: em builder usar `reorder()->count()` (no Postgres, COUNT com ORDER BY quebra); modo ids vazio → 0 sem query.
  - `lazy(int $chunk = 1000)`: `lazyById` no Eloquent, `lazy()` no QueryBuilder, `lazy()` na coleção.
  - Ids normalizados para string, únicos e sem vazios.
- **Testes** (`tests/Feature/SelectionTest.php`): cada tipo de fonte (model, Eloquent builder, query builder, coleção, callable que devolve builder/coleção/paginator via `applyAndPaginate`, vazia) × modos ids/all; busca, filtros e busca por coluna respeitados; exclusões; id fora do dataset ignorado; `count()` com ordenação ativa; `withoutPagination()`.

## T2 — Seleção de linhas na UI + contador

**Objetivo:** coluna de checkboxes, checkbox mestre, menu de seleção estilo Magento e contador.

- `DataTable` (construtor): `bool|string $selectable = false` (parse com `filter_var` como em `Filters::isCollapsible()`) e `bool|string $preserveSelectionOnFilter = false`. Novos prefixos de bag: `selection-th-`, `selection-td-`, `selection-checkbox-`. `hasTableActions()` passa a considerar `selectable`.
- `LwDataTable` (fora da URL):
  - Propriedades: `public array $selectedIds = []` (selecionados no modo ids, excluídos no modo all), `public bool $selectAllMatching = false`, `#[Locked] public ?int $matchingTotal = null`.
  - Métodos: `selectAllMatching()` (liga o modo all, limpa os ids e calcula `matchingTotal` via `Selection::count()`; em `LengthAwarePaginator` dá para usar `total()` no render), `deselectAll()`, `selection(): Selection` (**protegido**, ver notas da T1; os handlers recebem a `Selection` por injeção), `selectedCount(): int`.
  - `updatedSelectedIds()`: normaliza para strings únicas e limita a `config('erickcomp-livewire-data-table.max-selected-ids', 10000)`.
- **Reset da seleção:** em `updating()` para `search`/`filters`/`columnsSearch.*` e em `applyFilters()` (que atribui direto e não dispara `updating`), limpar a seleção, a menos que `preserve-selection-on-filter` esteja ligado. Nesse caso mantém os ids explícitos, mas sempre sai do modo all (como o `preserveSelectionsOnFilter` do Magento). Mudanças de per-page e de ordenação preservam a seleção.
- **View** (partials):
  - `<th>` inicial com checkbox mestre: marcado se toda a página estiver selecionada, `indeterminate` via `x-effect` quando só parte estiver; clique alterna a página.
  - Botão ▾ com o menu: "Selecionar todos (N)", "Desmarcar todos", "Selecionar todos desta página", "Desmarcar todos desta página".
  - `<td>` inicial em cada linha com `value=@js((string) data_get($row, identity))`, `x-bind:checked="isRowSelected(id)"` e `x-on:change="toggleRow(id)"`; classe de linha selecionada via `x-bind:class` (preset `table.tbody.tr.selected.class`).
  - `<th>` vazio na linha de busca por coluna e `colspan` +1 no "nenhum dado".
- **Cliente:** no `xData()`, `selectedIds: $wire.$entangle('selectedIds')` e `selectAllMatching: $wire.$entangle('selectAllMatching')` (deferidos), mais `isRowSelected`, `toggleRow`, `selectPage(ids)`, `deselectPage(ids)` e `selectedCount` (no modo all: `matchingTotal - excluídos`). Os ids da página são renderizados em JSON num atributo do `<th>` mestre. Sempre atribuir arrays novos, nunca mutar no lugar.
- **Contador** na linha `bulk-actions-and-per-page`: "N registros encontrados (M selecionados)" via `trans_choice` (total de `LengthAwarePaginator::total()` ou `count()` da coleção; em simple/cursor mostra só os selecionados). Aparece quando `selectable` ou com o atributo `records-count`.
- Presets: `table.selection.{th,td,checkbox,menu-button,menu,menu-item}.class` e `actions.records-count.class`. Lang: `select_all`, `deselect_all`, `select_page`, `deselect_page`, `records_found`, `records_selected`.
- **Testes:** render com e sem `selectable` (sem ⇒ HTML igual ao atual); `selectAllMatching()` calcula o total com filtros; reset em search/filters/applyFilters/columnsSearch e o `preserve`; normalização e limite de ids; `selection()` reflete o estado. Browser: marcar linhas, trocar de página e manter, menu de seleção, contador.

## T3 — Bulk actions (mass actions)

**Objetivo:** dropdown "Ações" com ações executadas no servidor (handler PHP) ou despachadas como evento Livewire, com sub-ações, confirmação, ACL e mensagem de resultado.

**API Blade:**
```blade
<x-data-table :data-src="App\Models\User::class" selectable>
    <x-data-table.bulk-actions placeholder="Ações">
        <x-data-table.bulk-action name="delete" label="Excluir"
            handler="App\DataTable\Users\DeleteUsers"
            confirm="Excluir :count registro(s)?" confirm-title="Excluir itens" can="delete-users" />
        <x-data-table.bulk-action name="status" label="Alterar status"
            handler="App\DataTable\Users\ChangeStatus@handle" :options="['1' => 'Ativar', '0' => 'Desativar']" />
        <x-data-table.bulk-action name="notify" label="Notificar" event="users-notify" keep-selection />
    </x-data-table.bulk-actions>
    ...
</x-data-table>
```
- Classes `src/DataTable/BulkActions.php` (container, bags `select-`) e `src/DataTable/BulkAction.php` (`name`, `label`, `handler?`, `event?`, `options[]`, `confirm?`, `confirmTitle?`, `can?`, `keepSelection`). Validar na definição: exatamente um entre `handler`/`event`, nomes únicos, handler resolvível via `ServerCallable`. A presença de bulk actions implica `selectable`.
- Componentes raw `x-data-table.bulk-actions` (filho direto de `x-data-table`, no máximo um) e `x-data-table.bulk-action` (filho de `bulk-actions`, self-closing).
- Criar a infra transversal: `src/Support/ServerCallable.php` (extraído de `CallableDataSource`, cujos testes têm que continuar verdes), `src/Support/ActionResultHandler.php` e o preset `confirm-dialog` (a função JS é injetada no `@assets`, como o `reload-alert`).
- `LwDataTable::executeBulkAction(string $name, ?string $option = null)`, em ordem:
  1. Localizar a ação; se houver `options`, a opção tem que pertencer a elas.
  2. Seleção não vazia (senão, mensagem de erro).
  3. `Gate::authorize($can)` quando definido.
  4. `$selection = $this->selection()`.
  5. Handler: `app()->call(..., ['selection' => $selection, 'option' => $option, 'action' => $name])`.
  6. Evento: `$this->dispatch($event, action:, option:, ids: $selection->ids(), count:)`.
  7. Retorno do handler: `null`, `string` ou `BulkActionResult::success|error|info($msg)` viram mensagem; `Redirector`/`RedirectResponse`/download (`Symfony\Response`) são devolvidos (o Livewire trata redirect e download).
  8. Exceção `ErrorMessageForUserException` vira mensagem de erro.
  9. Sucesso limpa a seleção (exceto com `keep-selection`) e re-renderiza.
- **Mensagens:** `public array $flash = []` em `LwDataTable`, renderizado acima da tabela com preset `messages.{success,error,info}.class` e botão de fechar (Alpine esconde e faz `$wire.flash = []` deferido).
- **UI:** `<select>` na linha `bulk-actions-and-per-page` (chaves de preset que já existem), com sub-ações em `<optgroup>`. Ao mudar: sem seleção ⇒ alerta `bulk_actions_no_selection`; com `confirm` ⇒ `confirm-dialog` (substitui `:count` pelo total selecionado); depois `$wire.executeBulkAction(name, option)`; o select volta ao placeholder. A condição do bloco passa a ser `perPage > 1 || hasBulkActions`.
- Lang: `bulk_actions_placeholder`, `bulk_actions_no_selection`, `confirm_default_title`, `confirm_default_message`, `bulk_action_done`.
- **Testes:** guardas dos componentes raw; validação das definições (handler+evento, nenhum dos dois, nome duplicado, closure); `set('selectedIds', [...])->call('executeBulkAction', 'delete')` aplica efeito no `TestProduct`; opção inválida; gate negado; `assertDispatched` no modo evento; modo all com exclusões; mensagem renderizada; seleção limpa ou mantida; `assertRedirect` e `assertFileDownloaded`. Browser: selecionar, escolher a ação, confirmar e ver a tabela atualizada.

## T4 — "Limpar tudo" nos filtros ativos + evento de recarga

- Botão "Limpar tudo" no container `applied-filters` quando há pelo menos 1 chip. Novo método `LwDataTable::clearAllFilters()`: zera `search`, `filters` e `rawFilters`, faz `resetPage` e aplica a regra de reset de seleção da T2, se existir. No store Alpine, `clearAll()` reseta `inputSearch`/`inputFilters` (formato de `computeInitialFilters()` vazio) e chama o método. Preset `applied-filters.button-clear-all.class`; lang `clear_all_filters_label`.
- Recarga externa: `#[On('lw-dt-refresh')] public function refreshTable(?string $table = null)`, que re-renderiza quando `$table` é nulo ou bate com o `id`/`name` da tabela (`DataTable::__get('id'|'name')`). No modo all, recalcula `matchingTotal`. Documentar `Livewire.dispatch('lw-dt-refresh', { table: 'users' })` e `$this->dispatch('lw-dt-refresh')`.
- **Testes:** `clearAllFilters` limpa o estado e a página; o botão só aparece com filtros; o evento com e sem `table` (`Livewire::test(...)->dispatch('lw-dt-refresh', table: 'x')`).

## T5 — Coluna de ações por linha + clique na linha

**API:**
```blade
<x-data-table.row-actions title="Ações" display="dropdown">  {{-- dropdown | links --}}
    <x-data-table.row-action name="edit" label="Editar" url="/users/{id}/edit" />
    <x-data-table.row-action name="show" label="Ver" route="users.show" :route-params="['user' => 'id']" />
    <x-data-table.row-action name="delete" label="Excluir" handler="App\DataTable\Users\DeleteUser"
        confirm="Excluir este registro?" can="delete" />
    <x-data-table.row-action name="archive" label="Arquivar" url="/users/{id}" method="delete" />
    <x-data-table.row-action name="notify" label="Notificar" event="user-notify" />
</x-data-table.row-actions>
```
- `RowActionsColumn extends Column` (não ordenável nem buscável; respeita os prefixos `th-`/`td-`), adicionada em `$this->columns` na posição em que foi declarada. A view ganha um `@elseif ($column instanceof RowActionsColumn)` no loop de células.
- `RowAction`: `name`, `label`, um destino (`url` com placeholders `{campo}` → `rawurlencode(data_get($row, campo))` | `route` + `route-params` | `handler` | `event`), `method` (get/post/put/patch/delete; diferente de GET ⇒ form oculto com `@csrf` + `_method` submetido via Alpine), `target`, `confirm`, `can`. Validação na definição. URLs só `http(s)` ou relativas (rejeitar `javascript:`).
- Visibilidade por linha: se houver `can`, `Gate::allows($can, $row)`; ações negadas não aparecem. Com uma única ação visível, renderiza um link simples (como o Magento).
- `LwDataTable::executeRowAction(string $name, string $rowId)`: verifica que a linha pertence ao dataset (`makeSelection(MODE_IDS, [$rowId])->rows()->first()`), revalida o `can` com a linha, chama o handler com `row`, `id`, `selection` (via `ServerCallable` + `ActionResultHandler` da T3) ou despacha o evento com `id`.
- **Clique na linha:** atributos `row-click="<nome de row-action>"` ou `row-click-url="/users/{id}/edit"` no `<x-data-table>`. O `<tr>` ganha `data-lw-dt-row-id` e `x-on:click="onRowClick($event, id)"`, ignorando cliques em `a, button, input, select, textarea, label, [data-lw-dt-no-row-click]`; classe de preset `table.tbody.tr.clickable.class`.
- **Testes:** placeholders e rotas; método não-GET gera form com CSRF; `can` por linha; `executeRowAction` com linha fora do dataset ⇒ erro; handler e evento; atributos de row-click. Browser: dropdown, confirmação e clique na linha.

## T6 — Estado na URL (`perPage`) + lembrar o último estado na sessão

**Objetivo:** substituir os bookmarks do Magento pelo que a URL já oferece. A URL passa a carregar todo o estado (o favorito do navegador vira a "view"), e um opt-in restaura o último estado quando o usuário volta à tela sem parâmetros.

- **`perPage` na URL:**
  - Novo param `?string $perPageName = null` no construtor do `DataTable`, config `query-string-per-page` (`per-page`) e chave de preset, como os outros `*UrlParam()` de `LwDataTable`.
  - Em `queryString()`, `'perPage' => ['as' => $this->perPageUrlParam(), 'except' => <primeira opção>]`.
- **Sanitização do `perPage`** (corrige uma brecha atual: hoje o cliente pode mandar qualquer valor, e pela URL isso fica trivial): no mount/hydrate e em `updatingPerPage`, um valor fora das chaves de `perPageOptionsForSelect()` volta para a primeira opção.
  - **Investigar:** `public ?string $perPage = '15'` nunca é alinhado às opções definidas (ex.: `per-page="10,25"` ou o padrão `['all']` de fontes não-Eloquent). Alinhar no mount à primeira opção e, se isso mudar o comportamento atual, registrar no commit.
- **Lembrar estado (opt-in):** atributos `state-key="users"` (obrigatório para ligar; identifica a tabela de forma estável) e `remember-state` no `<x-data-table>`.
  - Estado guardado na sessão (`lw-dt-state.{state-key}`): search, filters, columnsSearch, sortBy, sortDir e perPage. A página não é guardada (o Magento também não guarda).
  - No mount, se a URL não trouxer nenhum parâmetro da tabela (search, filters, cols-search, sortBy, sortDir, per-page, page), restaura o estado salvo sanitizado, reaproveitando `sanitizeSortBy`/`sanitizeColumnsSearch` (filtros só com pares dataField/name definidos; perPage ∈ opções).
  - Conferir se o Livewire reflete o estado restaurado na URL; se não, sincronizar via `history.replaceState`.
  - No `dehydrate()`, grava só se o hash do estado mudou.
  - Se a T2 existir, a seleção **não** entra no estado.
- README: seção "Estado e URL" explicando que dá para salvar favoritos, como mudar os nomes dos parâmetros e o `remember-state`.
- **Testes:** `perPage` lido da/escrito na URL com `except`; valor inválido sanitizado; restauração só sem parâmetros na URL; estado adulterado na sessão sanitizado; `state-key` isola tabelas.

## T7 — Exportação com adapters (OpenSpout / PhpSpreadsheet)

**Objetivo:** exportar os registros filtrados (todas as páginas) ou só os selecionados, usando a biblioteca de planilhas que estiver instalada.

- **Dependências:** `composer.json` → `suggest` com `openspout/openspout` (recomendada: streaming com memória constante) e `phpoffice/phpspreadsheet`; as duas em `require-dev` para os testes.
- **Adapters:**
  - Interface `src/Export/ExportWriter.php`: `isAvailable(): bool`, `supportedFormats(): array`, `write(string $format, array $header, iterable $rows, string $path): void`.
  - `src/Export/OpenSpoutWriter.php`: csv, xlsx, ods, escrevendo em streaming.
  - `src/Export/PhpSpreadsheetWriter.php`: csv, xlsx, ods; monta a planilha em memória (documentar). Usar sempre `setCellValueExplicit(..., DataType::TYPE_STRING)` para texto, senão um valor que começa com `=` vira fórmula.
  - `src/Export/ExportWriterResolver.php`: config `export.writer` = `auto|openspout|phpspreadsheet`; no `auto`, OpenSpout tem prioridade. A checagem de classe fica num método sobrescrevível, para testar o caso "nenhuma lib".
- **Componente** raw `<x-data-table.export formats="csv,xlsx" filename="produtos-{date}" can="export-products" />` (self-closing, filho de `x-data-table`). Sem lib disponível ⇒ `LogicException` com instruções de instalação (mesmo padrão do `EloquentBuilderDataSource` com `laravie/serialize-queries`). Formato não suportado pelo writer ativo ⇒ erro na definição. UI: botão/dropdown "Exportar" na linha de ações.
- **`LwDataTable::export(string $format)`:** valida o formato e o `can`.
  - Linhas: se houver seleção (T2) ⇒ `selection()->lazy()`; senão, `getUnpaginatedData()` (T1) em lazy, respeitando busca, filtros e ordenação.
  - Colunas: todas, exceto `exportable="false"`, a coluna de seleção e a de ações (T5).
  - Grava num arquivo temporário e devolve `response()->download(...)->deleteFileAfterSend()`.
- **Valores:** `DataColumn` → `getCellData()` (datas/Stringable convertidos para string). `CustomRenderedColumn` → `export-field`, se houver; senão `dataField`; senão renderiza o Blade e aplica `strip_tags` + `html_entity_decode` + trim (documentar o custo).
- **Segurança:** no CSV, prefixa `'` em valores que começam com `= + - @ \t \r` (config `export.escape-formulas`, padrão true). `export.max-rows` opcional; respeita `php-max-memory`.
- **Testes:** os dois adapters, forçados via config; resolver em `auto` e sem lib; conteúdo do CSV via `assertFileDownloaded`; XLSX lido de volta com a própria lib; escape de fórmulas; exportação só da seleção; `exportable="false"`; `can` negado.

## T8 — Edição inline (uma linha)

- Atributos do `<x-data-table>`: `inline-edit="App\DataTable\Products\SaveProduct"` (handler via `ServerCallable`), `inline-edit-trigger="row-click|button"` e `inline-edit-can`. Atributos de coluna: `editor="text|number|select|date|datetime|checkbox|textarea"`, `:editor-options`, `editor-rules="required|max:100"`, `editor-field` (padrão: dataField).
- **Estado no servidor:** `public ?string $editingRowId = null; public array $editingValues = [];`
  - `editRow(id)`: carrega os valores da linha verificada via `Selection` e checa o gate com a linha.
  - `saveInlineEdit()`: aceita só os campos com `editor`; `Validator::make` com as regras, erros em `editingValues.{campo}`; chama o handler `(id, values, row)`. `ValidationException` do handler vira erro por campo; sucesso gera mensagem (infra da T3), sai do modo edição e re-renderiza.
  - `cancelInlineEdit()`.
- **View:** na linha em edição, as células editáveis viram inputs com `wire:model="editingValues.campo"`, com erros abaixo; botões Salvar/Cancelar (Enter salva, Esc cancela). Uma linha por vez. Com `trigger=button`, botão "Editar" na coluna de ações (T5, se existir) ou numa coluna própria. Com `trigger=row-click`, tem precedência sobre o `row-click` da T5.
- **Testes:** só campos editáveis aceitos; linha fora do dataset rejeitada; validação; gate; handler chamado; cancelar. Browser: editar e salvar.

---

## Verificação (fim a fim)

- Por tarefa: `vendor/bin/pest` (Unit + Feature), `vendor/bin/pest --testsuite=Browser` (depois da T0) e `vendor/bin/phpstan analyse --memory-limit=512M` sem novas entradas no baseline; recursos JS também com Livewire 3 (`--with=livewire/livewire:^3.6`).
- Demo manual: `composer serve` (workbench da T0) → `/demo/vanilla`, `/demo/bootstrap4v1`, `/demo/tailwind3v1`, exercitando cada recurso novo.
- CI (`.github/workflows/tests.yml`): matriz PHP 8.3/8.4 × Laravel 12/13, o passo com autoloader `--classmap-authoritative`, PHPStan e o job de browser.
