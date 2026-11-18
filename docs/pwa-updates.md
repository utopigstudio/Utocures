# Actualitzacions de marca de la PWA

El manifest conserva l’adreça `/manifest.webmanifest` i l’identificador `/`.
Els colors actuals són `#F6F3F2` per al fons d’arrencada i `#501A46` per al tema.

Les adreces de les icones porten `?v=` seguit dels primers 12 caràcters del
SHA-256 del fitxer. Els fitxers originals es conserven sense modificacions.
La versió només ha de canviar quan canviï la imatge, no a cada desplegament.

Quan es substitueixi una icona, cal recalcular aquesta versió i actualitzar
les seves referències al manifest, a `resources/views/app.blade.php`, al
component `PwaInstallPrompt`, a `public/sw.js` i a `NewMessagePushNotification`,
segons on s’utilitzi. Això permet als navegadors detectar el canvi d’adreça
i descarregar la imatge nova.

En Apache amb `mod_headers`, `public/.htaccess` serveix el manifest i el
service worker amb `Cache-Control: no-cache`, perquè es revalidin. Si el
servidor de producció o una CDN els serveix directament amb una altra
configuració, també ha de respectar aquesta política.

Després de publicar, cal comprovar les capçaleres i el contingut del manifest
i de les icones des del domini de producció i provar una instal·lació existent.
Els colors es poden renovar automàticament. Un canvi important d’icona pot
requerir confirmació del navegador; el web no pot forçar aquesta acceptació.
No s’afegeixen avisos ni instruccions a les usuàries.

Referència: [actualitzacions de PWA a Chrome 144 i posteriors](https://developer.chrome.com/blog/improvements-to-web-app-updates).
