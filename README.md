# Mural 17 — tema WordPress

Tema do portal **Mural 17**: notícias, anúncios, eventos e empregos das 117 cidades do DDD 17, organizadas em 8 regiões.

## O que o tema traz

- **Home "mural vivo":** o topo "Hoje no 17" com os números do dia e atalhos em bolinhas; um painel em blocos com manchete, plantão (últimas notícias com horário), evento e vaga do dia, mais lidas, trânsito, classificado e o convite para mandar notícia pelo WhatsApp; depois notícias por região, agenda da semana e classificados. Tudo se monta sozinho com o que é publicado.
- **Páginas de região e de cidade** (`/cidade/regiao-catanduva`, `/cidade/mirassol`) com o mesmo mural, só com o que é de lá, e a lista completa de notícias embaixo.
- **"Escolha sua cidade":** busca entre as 117 cidades no cabeçalho; a escolha fica guardada no aparelho da pessoa.
- **Três tipos de conteúdo próprios** no painel:
  - **Eventos** (`/agenda`): data, horário, local, entrada e link. A agenda mostra só os eventos futuros, em ordem de data.
  - **Vagas** (`/empregos`): empresa, tipo (CLT, concurso etc.), salário, contato e prazo. O botão "Quero me candidatar" abre o WhatsApp, o e-mail ou o link informado.
  - **Classificados** (`/classificados`): preço, WhatsApp do anunciante e categorias (Veículos, Imóveis, Móveis e casa, Serviços, Diversos).
- **Cidade** como taxonomia comum a notícias, eventos, vagas e anúncios, com as regiões em cima e as cidades dentro (região inclui as cidades nas listagens). As listagens de agenda, empregos e classificados têm filtro por região.
- **Notícias agregadas por RSS:** a matéria mostra "Fonte: …" e o botão "Ler na fonte original". O tema lê o link original gravado pelo **WP RSS Aggregator** ou pelo **Feedzy**. Se preferir, preencha à mão a caixa "Fonte da notícia" no editor.
- **Mais lidas:** contador de visualizações próprio. Ignora robôs e quem está logado como editor.
- **Áreas de anúncio** (Aparência › Widgets): topo e meio da home (728×90), lateral (300×250) e dentro da matéria. É só colar o código do AdSense num widget "HTML personalizado".
- Compartilhamento por WhatsApp, Facebook e X, sem scripts de terceiros.
- Responsivo, com menu próprio no celular.

## Instalação

1. Baixe o tema: **Code › Download ZIP** neste repositório.
2. Descompacte e renomeie a pasta `mural17-main` para `mural17`. Compacte de novo em `mural17.zip`.
3. No WordPress, vá em **Aparência › Temas › Adicionar novo › Enviar tema** e envie o `mural17.zip`.
4. Ative o tema. Na ativação, ele cria sozinho:
   - as 8 regiões e as 117 cidades do DDD 17 (em sites que já usavam o tema, isso acontece no primeiro acesso ao painel);
   - as categorias Polícia e Trânsito;
   - as categorias de classificados.
5. Em **Configurações › Links permanentes**, escolha "Nome do post" e salve.

## Configuração

Em **Aparência › Personalizar › Mural 17**:

| Opção | Para que serve |
|---|---|
| Cor de destaque | Vermelho sinal (#E5232D) por padrão; muda botões, chapéus e detalhes |
| Slogan | Texto abaixo do logo ("Tudo que acontece no 17") |
| WhatsApp da redação | Ativa os botões "Envie sua notícia", "Divulgar evento", "Anunciar vaga" e "Anunciar". Use só números, com 55 e DDD (ex.: 5517999999999) |
| E-mail, Instagram, Facebook | Rodapé |
| Faixa "Última hora" | Liga ou desliga a faixa com as notícias das últimas 48 h |

Sem logo escolhido em **Personalizar › Identidade do site**, o tema usa o logo da marca (`assets/marca/`) no cabeçalho e no rodapé, e o favicon da marca. Cores, versões do logo e regras de uso estão em [`assets/marca/README.md`](assets/marca/README.md).

**Menus** (Aparência › Menus): o tema tem três posições, *Menu principal*, *Barra do topo* e *Rodapé*. Se o menu principal ficar vazio, o tema monta um automático com Polícia, Trânsito, Empregos, Classificados e Agenda. As cidades ficam no botão "Escolha sua cidade".

**Manchete:** marque a notícia como **Fixar no topo do blog** para ela virar a manchete da home e da página da cidade dela (vale por 3 dias). Sem post fixado, entra a mais recente com foto.

## Como publicar cada coisa

- **Notícia de uma cidade:** Posts › Adicionar, marque a **Cidade**. Para polícia ou trânsito, marque também a categoria. Escreva uma linha fina no campo **Resumo**.
- **Evento:** Eventos › Adicionar, preencha **Data** (obrigatória para aparecer na agenda), horário e local, e marque a cidade.
- **Vaga:** Vagas › Adicionar, preencha o tipo e "Como se candidatar".
- **Classificado:** Classificados › Adicionar, coloque foto destacada, preço e WhatsApp do anunciante.

## Feeds RSS (notícias automáticas)

Use o plugin **WP RSS Aggregator** ou o **Feedzy RSS Feeds** e importe cada feed como **post**, já na **cidade** certa. Importe só o título, um resumo curto e o link da fonte, **nunca o texto completo**: republicar matéria inteira de outro veículo é violação de direito autoral e pode derrubar o site e o AdSense.

Sugestões de feeds (teste cada um no navegador antes de cadastrar):

| Feed | Cidade / categoria |
|---|---|
| `https://g1.globo.com/rss/g1/sp/sao-jose-do-rio-preto-aracatuba/` | Rio Preto |
| `https://news.google.com/rss/search?q=%22Neves+Paulista%22&hl=pt-BR&gl=BR&ceid=BR:pt-419` | Neves Paulista |
| `https://news.google.com/rss/search?q=%22Jaci%22+SP&hl=pt-BR&gl=BR&ceid=BR:pt-419` | Jaci |
| `https://news.google.com/rss/search?q=Mirassol+SP+-futebol&hl=pt-BR&gl=BR&ceid=BR:pt-419` | Mirassol |
| `https://news.google.com/rss/search?q=%22Rio+Preto%22+(pol%C3%ADcia+OR+acidente+OR+pris%C3%A3o)&hl=pt-BR&gl=BR&ceid=BR:pt-419` | Polícia |

## Notas oficiais (prefeituras, câmaras e Justiça)

Órgãos públicos do DDD 17 podem enviar comunicados, que saem com o selo azul **Oficial**:

- **Cadastro:** a página *Cadastro de órgão* (criada sozinha) aceita só e-mail institucional (`.sp.gov.br`, `.sp.leg.br`, `.jus.br`, `.mp.br`, `.def.br`, `.gov.br`). O pedido chega por e-mail e aparece em **Posts › Notas oficiais › Pedidos de cadastro**. Confirme pelo telefone oficial e clique em *Liberar acesso*: o órgão recebe o link para criar a senha.
- **Envio:** o órgão entra na página *Painel do órgão* (não tem acesso ao painel do WordPress) e envia título, resumo, texto, foto e o bloco "Serviço". A cidade é a do órgão.
- **Revisão:** as notas caem em **Posts › Notas oficiais**, com *Aprovar e publicar*, *Revisar texto* e *Devolver* (o órgão vê o motivo e pode reenviar). Na aba *Órgãos ativos* dá para ligar a **publicação direta** para órgãos confiáveis.
- **Período eleitoral:** no topo da mesma tela, defina as datas (ou ligue à mão). Em vigor, nada de prefeituras, câmaras e órgãos municipais é publicado sozinho; tudo cai na fila, marcado, para você aprovar só aviso urgente. Judiciário, Ministério Público, Defensoria e polícias seguem normais. Confirme as datas e as regras com a assessoria jurídica.
- **No site:** a nota tem faixa "Nota oficial", quadro "Serviço" e cartão do órgão; cada órgão tem a página `/orgao/<nome>/`; as páginas de cidade e região mostram a faixa "Comunicados oficiais"; no Plantão aparece "Oficial · <órgão>". Notas oficiais não viram manchete automática (fixar à mão continua valendo).
- **Brasão:** em **Posts › Órgãos oficiais**, edite o órgão para escolher o brasão, o tipo, a cidade e os contatos. Sem brasão, aparecem as iniciais.

## Portal de Notícias PRO

O tema já conversa com o plugin **Portal de Notícias PRO — Suite**:

- **Fonte da matéria:** o tema lê o endereço de origem que o plugin grava e mostra a caixa "Fonte" e o botão "Ler na fonte original". Deixe o "Crédito da fonte" do plugin **desligado**, senão a fonte aparece duas vezes.
- **Cidade por feed:** ao ativar o tema, ele liga a opção do plugin que mostra a taxonomia **Cidade** no cadastro de cada feed. Escolha a cidade do feed e toda matéria importada já entra nela (e aparece na home, na página da cidade e no bloco "Por cidade").
- **Cards do Instagram e Stories:** use a logo personalizada com `assets/marca/mural17-logo-negativo.png` (fundo escuro) ou `mural17-logo.png` (fundo claro). Cores da marca: vermelho `#E5232D`, tinta `#0E1014`, amarelo `#FFC531`.

## Plugins recomendados

- **WP RSS Aggregator** ou **Feedzy**: notícias automáticas.
- **Rank Math**: SEO e sitemap para o Google Notícias.
- **LiteSpeed Cache** (ou o cache da sua hospedagem): velocidade.
- **Site Kit by Google**: Analytics e AdSense.

## Estrutura

```
mural17/
├── style.css              cabeçalho do tema
├── functions.php          configuração, menus, widgets, scripts
├── inc/
│   ├── post-types.php     eventos, vagas, classificados e cidades
│   ├── meta-boxes.php     campos extras de cada tipo
│   ├── customizer.php     opções em Aparência › Personalizar
│   ├── helpers.php        funções usadas nos templates
│   ├── mural.php          dados do mural (números do dia, plantão, agenda da semana)
│   ├── oficial*.php       notas oficiais: órgãos, cadastro, painel do órgão e tela da redação
│   ├── cidades.php        as 8 regiões e as 117 cidades do DDD 17
│   └── setup-content.php  cidades e categorias criadas na ativação
├── template-parts/        cartões e blocos reutilizados (mural.php é a home)
├── front-page.php         home
├── taxonomy-cidade.php    página de região e de cidade
├── taxonomy-orgao.php     página de cada órgão oficial
├── single*.php            matéria, evento, vaga, classificado
├── archive-*.php          agenda, empregos, classificados
├── assets/                CSS, JS e a marca (assets/marca)
└── docs/                  levantamento das fontes de notícia do DDD 17
```

Requer WordPress 6.2+ e PHP 7.4+.
