"""Levanta as fontes de notícia das cidades do DDD 17.

Para cada cidade testa o site da prefeitura e da câmara (domínios padrão
.sp.gov.br e .sp.leg.br), procura o RSS (link no <head> e caminhos comuns) e
grava tudo em fontes_ddd17.csv.

Uso: python3 levantar_fontes.py cidades.csv
cidades.csv: colunas "cidade" e "regiao" (região imediata do IBGE).
"""
import concurrent.futures as cf
import csv
import re
import sys
import unicodedata
import urllib.parse
import urllib.request

UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36"
CAMINHOS_RSS = ["/portal/rss", "/feed/", "/rss", "/rss.xml", "/noticias/feed/", "/noticias/rss", "/index.php?format=feed&type=rss"]


def slug(nome):
    s = unicodedata.normalize("NFKD", nome).encode("ascii", "ignore").decode().lower()
    return re.sub(r"[^a-z0-9]", "", s)


def baixar(url, limite=400_000):
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=12) as r:
        return r.geturl(), r.headers.get("Content-Type", ""), r.read(limite).decode("utf-8", "ignore")


def e_feed(tipo, corpo):
    return "xml" in tipo or bool(re.search(r"<(rss|feed)[\s>]", corpo[:2000]))


def procurar_site(candidatos):
    for url in candidatos:
        try:
            final, _, html = baixar(url)
            return final, html
        except Exception:
            continue
    return "", ""


def procurar_rss(site, html):
    m = re.search(r'<link[^>]+type="application/(?:rss|atom)\+xml"[^>]*>', html, re.I)
    if m:
        href = re.search(r'href="([^"]+)"', m.group(0))
        if href:
            return urllib.parse.urljoin(site, href.group(1))
    for caminho in CAMINHOS_RSS:
        url = urllib.parse.urljoin(site, caminho)
        try:
            _, tipo, corpo = baixar(url, 4000)
            if e_feed(tipo, corpo):
                return url
        except Exception:
            continue
    return ""


def levantar(linha):
    s = slug(linha["cidade"])
    pref, html_p = procurar_site([f"https://www.{s}.sp.gov.br/", f"https://{s}.sp.gov.br/", f"http://www.{s}.sp.gov.br/"])
    cam, html_c = procurar_site([f"https://www.{s}.sp.leg.br/", f"https://{s}.sp.leg.br/", f"https://www.camara{s}.sp.gov.br/"])
    return {
        "regiao": linha.get("regiao", ""),
        "cidade": linha["cidade"],
        "prefeitura": pref,
        "rss_prefeitura": procurar_rss(pref, html_p) if pref else "",
        "camara": cam,
        "rss_camara": procurar_rss(cam, html_c) if cam else "",
        "google_news": "https://news.google.com/rss/search?q=" + urllib.parse.quote(f'"{linha["cidade"]}" SP') + "&hl=pt-BR&gl=BR&ceid=BR:pt-419",
    }


def main():
    with open(sys.argv[1], encoding="utf-8") as f:
        cidades = list(csv.DictReader(f))
    with cf.ThreadPoolExecutor(12) as ex:
        linhas = list(ex.map(levantar, cidades))
    linhas.sort(key=lambda l: (l["regiao"], l["cidade"]))
    with open("fontes_ddd17.csv", "w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=list(linhas[0]))
        w.writeheader()
        w.writerows(linhas)
    com_rss = sum(1 for l in linhas if l["rss_prefeitura"] or l["rss_camara"])
    print(f"{len(linhas)} cidades, {com_rss} com pelo menos um RSS oficial")


if __name__ == "__main__":
    main()
