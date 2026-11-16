# Paleta de color d’Utocures

La font de veritat de la interfície és `resources/css/app.css`. Es manté
l’arquitectura de Tailwind 4 (`@theme inline`), les variables semàntiques dels
components i les variants existents. No s’han afegit dependències.

## Colors base

| Ús | HEX |
| --- | --- |
| Fons de pàgina | `#F6F3F2` |
| Sidebar i fons d’accés | `#501A46` |
| Primary | `#701D61` |
| Warning | `#EF8C55` |
| Danger | `#EF284C` |
| Success | `#03C373` |

Els grisos de Figma es conserven exactament a `neutral-50` fins a `neutral-900`:
`#F6F4F4`, `#ECEAE9`, `#DEDAD9`, `#C8C2C0`, `#AAA09D`, `#887A77`, `#6A605C`,
`#47403E`, `#2E2A28`, `#191615`. `neutral-950` reutilitza el més fosc.

## Variants i compatibilitat

- `primary-600` és el primary exacte; `warning-500` i `success-500` són
  els colors base exactes del logo. `danger-500` és `#EF284C`, un vermell
  ajustat per diferenciar-se millor del primary.
- Les variants clares es barregen amb blanc i les fosques amb negre mitjançant
  `color-mix(in srgb, ...)`. Les proporcions són explícites al mateix CSS.
- Per a text sobre superfícies clares, utilitzar els tons `600` o superiors,
  o `text-muted-foreground` per al text secundari.
- `gray-*`, `blue-gray-*` i `slate-*` apunten als grisos nous. Els antics tons
  `500` utilitzats per a text apunten a `neutral-600`, ja que `neutral-500`
  només dona 4,12:1 sobre blanc i 3,73:1 sobre el fons de pàgina.
- Les classes existents `blue`/`indigo`/`violet`/`sky` utilitzades per la UI
  apunten a primary; `red`/`rose` a danger; `amber` a warning i
  `green`/`emerald` a success. Això evita reescriure tots els components.
- Sidebar, calendaris, notificacions, icones, mapes i barra de progrés
  consumeixen aquestes variables. El manifest PWA replica el fons i la sidebar.
- Es manté intacta la funció `darken()` de `resources/js/lib/utils.ts` i
  l’ús dels colors configurables dels serveis. Aquesta funció no genera
  la paleta general de la interfície.

## Contrast de les variants de text

Valors calculats amb luminància relativa sRGB, arrodonits per mostrar-los:

| Variant | HEX aproximat | Sobre blanc | Sobre el fons de pàgina |
| --- | --- | --- | --- |
| Primary | `#701D61` | 10,31:1 | 9,34:1 |
| Danger 600 | `#CB2241` | 5,46:1 | 4,94:1 |
| Warning 600 | `#8F5433` | 6,02:1 | 5,45:1 |
| Success 600 | `#027545` | 5,78:1 | 5,24:1 |
| Text secundari | `#6A605C` | 6,10:1 | 5,53:1 |

S’han comprovat també les variants de text sobre els fons de cada estat.
Aquests resultats validen les combinacions de paleta, no una auditoria
completa d’accessibilitat de l’aplicació.

Referències: [WCAG 2.2, contrast del text](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html)
i [contrast dels elements gràfics](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html).
