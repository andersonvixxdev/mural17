# Mural 17 — tema WordPress

Tema do portal **Mural 17**: notícias, anúncios, eventos e empregos de Neves Paulista, Mirassol, Jaci e São José do Rio Preto (DDD 17).

## O que o tema traz

- **Home de portal:** manchete principal e duas secundárias, notícias por cidade, polícia e trânsito, mais lidas, empregos, classificados e agenda.
- **Três tipos de conteúdo próprios** no painel:
  - **Eventos** (`/agenda`): data, horário, local, entrada e link. A agenda mostra só os eventos futuros, em ordem de data.
  - **Vagas** (`/empregos`): empresa, tipo (CLT, concurso etc.), salário, contato e prazo. O botão "Quero me candidatar" abre o WhatsApp, o e-mail ou o link informado.
  - **Classificados** (`/classificados`): preço, WhatsApp do anunciante e categorias (Veículos, Imóveis, Móveis e casa, Serviços, Diversos).
- **Cidade** como taxonomia comum a notícias, eventos, vagas e anúncios (`/cidade/mirassol`). As listagens de agenda, empregos e classificados têm filtro por cidade.
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
   - as cidades Neves Paulista, Mirassol, Jaci e Rio Preto;
   - as categorias Polícia e Trânsito;
   - as categorias de classificados.
5. Em **Configurações › Links permanentes**, escolha "Nome do post" e salve.

## Configuração

Em **Aparência › Personalizar › Mural 17**:

| Opção | Para que serve |
|---|---|
| Cor de destaque | Vermelho por padrão; muda botões, chapéus e detalhes |
| Slogan | Texto abaixo do logo ("Tudo que acontece no 17") |
| WhatsApp da redação | Ativa os botões "Envie sua notícia", "Divulgar evento", "Anunciar vaga" e "Anunciar". Use só números, com 55 e DDD (ex.: 5517999999999) |
| E-mail, Instagram, Facebook | Rodapé |
| Faixa "Última hora" | Liga ou desliga a faixa com as notícias das últimas 48 h |

Para o logo em imagem, use **Personalizar › Identidade do site**. Sem logo, o tema mostra "Mural**17**" em texto.

**Menus** (Aparência › Menus): o tema tem três posições, *Menu principal*, *Barra do topo* e *Rodapé*. Se o menu principal ficar vazio, o tema monta um automático com as cidades, Polícia, Trânsito, Empregos, Classificados e Agenda.

**Destaques da home:** marque a notícia como **Fixar no topo do blog** para ela virar a manchete principal. Sem posts fixados, entram as três mais recentes.

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
│   └── setup-content.php  cidades e categorias criadas na ativação
├── template-parts/        cartões e blocos reutilizados
├── front-page.php         home
├── single*.php            matéria, evento, vaga, classificado
├── archive-*.php          agenda, empregos, classificados
└── assets/                CSS e JS
```

Requer WordPress 6.2+ e PHP 7.4+.
