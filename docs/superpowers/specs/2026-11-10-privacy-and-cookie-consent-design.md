# Adatkezelési tájékoztató, űrlap-hozzájárulás és cookie-kezelő — Design

**Date:** 2026-11-10
**Status:** Implemented (controller and retention decisions updated below)

## Intent

A Somfai Klímatechnika weboldal (statikus `index.html` + `contact.php`) most Google Tag Managert használ (`GTM-NLDRTWG5`, az ügynökség kezeli), ezért jogszabályi okból kell: (1) adatkezelési tájékoztató, (2) kötelező hozzájárulás-jelölőnégyzet a kapcsolati űrlapon, (3) cookie-hozzájárulás kezelő. Mintaként az `https://aktivdigital.hu/adatkezelesi-tajekoztato` szolgál. A weboldal és a tartalom magyar nyelvű.

## Decisions (by the site owner)

- **Adatkezelő (végleges):** Somfai Klímatechnika Kft., 2500 Esztergom, Lázár Vilmos utca 73., cégjegyzékszám 11 09 032189, adószám 33064385-2-11. Minden adatot a Somfai kezel; az Aktiv Digital nem szerepel a tájékoztatóban.
- **Megőrzés (végleges):** az űrlapüzenetek határozatlan ideig, törlési kérelemig. CRM egyelőre nem része.
- Cookie sáv: "Elfogadom mindet", "Csak a szükségesek", "Beállítások"; kategóriánként választható (szükséges = mindig be, statisztikai, marketing); választás böngészőben tárolva; a lábrészből bármikor módosítható.
- Űrlap: kötelező jelölőnégyzet a tájékoztatóra hivatkozva, kliens- és szerveroldali (`contact.php`) ellenőrzéssel.
- A GTM csak hozzájárulás után engedélyezhet címkéket (Google Consent Mode v2, alapértelmezett "denied") — ez a tervező javaslata, a tulaj nem ellenezte.

## Design (builder's calls marked [call])

**Tájékoztató oldal** `adatkezelesi-tajekoztato.html`: ugyanaz a nav/footer/arculat mint a főoldal (`css/style.css`). Szakaszok az aktivdigital mintájából: adatkezelő; cookie-k (szükséges, felhasználói élmény, statisztikai, marketing — jogalap és hozzájárulás); kapcsolati űrlap (név, telefon, e-mail, tárgy, üzenet, IP/User-Agent; jogalap: GDPR 6(1)(f) / 6(1)(b) ajánlatkérésnél); címzettek és adatfeldolgozók (a beérkező üzeneteket a Somfai Klímatechnika info@ postafiókja fogadja; hosting: Tárhely.eu Kft.; Google Ireland Limited); biztonság; adatalanyi jogok; jogorvoslat (NAIH); módosítás. A Hotjar, Meta Pixel, CRM, SMS, mobilalkalmazás szakaszok kimaradnak. [call] A "felhasználói élmény" kategóriát nem kínáljuk külön kapcsolóként, mert az oldal nem használ ilyen cookie-t. [call] A tájékoztató szövege a mintából igazított; jogi felülvizsgálat nem része.

**Űrlap:** új jelölőnégyzet `privacy` az üzenet mező és a küldés gomb között, szöveg: "Elolvastam és elfogadom az adatkezelési tájékoztatót *" linkkel. A `js/main.js` `validateField` bővül (hiba: "A küldéshez el kell fogadnia az adatkezelési tájékoztatót."). `contact.php`: hiányzó `privacy` esetén 400 + `{"ok": false}`.

**Cookie-kezelő:** `js/consent.js` + stílus `css/style.css`-ben; sáv és beállítások panel az `index.html`-ben (és a tájékoztató oldalon). Tárolás: `localStorage` kulcs `somfai_consent` (kategóriák, időbélyeg, verzió) [call]. Consent Mode v2: a `consent.js` a GTM-snippet **előtt** fut a `<head>`-ben, `gtag('consent','default', {...denied...})`-et állít (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`), majd mentett választás vagy elfogadás után `gtag('consent','update', ...)`. Statisztikai = analytics_storage; marketing = az ad_* hármas. Lábrészben "Cookie-beállítások" link újranyitja a panelt. A sáv fókuszcsapdával/billentyűzettel kezelhető, `aria` attribútumokkal.

**Egyéb:** a láblécben link a tájékoztatóra; `sitemap.xml`-be az új oldal; a `docs/`-on kívül a deploy-t nem kell módosítani.

## Non-goals / anti-goals

- Nem készül szerveroldali hozzájárulás-napló.
- Nem módosítjuk a GTM-tárolót (az ügynökség kezeli). Anti-goal: olyan állapot, ahol hozzájárulás nélkül bármilyen követőcookie beállítódik.
- Nem jogi tanácsadás.

## Open for the builder

Az ügynökségtől függ, milyen címkék futnak a GTM-ben (Analytics, Ads remarketing, más); a tájékoztató a Google Analytics és Google Ads szolgáltatásokat nevezi meg. Ha más is fut, a szöveget bővíteni kell. A GTM-tárolóban a címkéknél be kell állítani a "consent" követelményeket (ügynökségi teendő).

## Builder-check resolutions [calls]

- A tájékoztató szövege **új, a Somfai-oldalra írt szöveg**, az aktivdigital.hu csak szerkezeti minta; nem másoljuk szó szerint.
- A GTM-snippet betölt Consent Mode default-denied mellett; nem blokkoljuk külön. Ami nem tartja tiszteletben a consentet (pl. Meta pixel), az ügynökségi konfiguráció; erről a tájékoztató nem állít többet.
- `contact.php`-t egyedül a `js/main.js` hívja, ezért a `privacy` mező kötelezővé tétele biztonságos.
- `contact.php` nem tárol IP-t/User-Agentet; a tájékoztató ezt nem állítja (csak a tárhely szerver-naplóit említi).
- Minden közönség-oldal (főoldal, tájékoztató) azonos `<head>` sorrendet kap; `/r/` és `qr-admin` nem kap GTM-et és sávot. A `somfai_consent` verziója változáskor újra megkérdez.

## Needs the owner's answer

(Mindkettő megválaszolva, lásd fent.)

## Testing

Böngészőben kézzel ellenőrizve: első látogatás (sáv látszik, `dataLayer` consent default denied), elfogadás/elutasítás, újranyitás, űrlap pipa nélkül/pipával; `contact.php` `privacy` nélkül 400-at ad (php -S-sel).
